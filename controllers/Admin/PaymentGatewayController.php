<?php
// /controllers/PaymentController.php

class PaymentController {
    private $paymentGatewayModel;
    private $userModel;
    private $transactionModel;
    private $db;

    public function __construct() {
        // Không yêu cầu đăng nhập ở constructor vì webhook cần được truy cập công khai
        // Việc kiểm tra đăng nhập sẽ được thực hiện trong từng phương thức cụ thể.
        if (class_exists('PaymentGatewayModel')) $this->paymentGatewayModel = new PaymentGatewayModel(); else die('FATAL ERROR: PaymentGatewayModel not found.');
        if (class_exists('UserModel')) $this->userModel = new UserModel(); else die('FATAL ERROR: UserModel not found.');
        if (class_exists('TransactionModel')) $this->transactionModel = new TransactionModel(); else die('FATAL ERROR: TransactionModel not found.');
        if (class_exists('Database')) $this->db = new Database(); else die('FATAL ERROR: Database class not found.');
    }

    /**
     * Hàm private để kiểm tra đăng nhập và chuyển hướng nếu cần cho các action của người dùng.
     */
    private function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? base_url();
            set_flash_message('auth_error', 'Vui lòng đăng nhập để thực hiện chức năng này.', MSG_INFO);
            redirect(base_url('user/login'));
            exit;
        }
    }

    /**
     * Hiển thị trang nhập số tiền cần nạp.
     * URL: /payment/deposit
     */
    public function deposit() {
        $this->requireLogin();
        $page_name_for_seo = 'deposit';
        $page_title = 'Nạp Tiền Vào Tài Khoản';
        
        $activeGateways = $this->paymentGatewayModel->getAllGateways(true); // Chỉ lấy các cổng đang active
        $data = [
            'gateways' => $activeGateways,
            'errors' => $_SESSION['deposit_errors'] ?? [],
            'input' => $_SESSION['deposit_input'] ?? []
        ];
        unset($_SESSION['deposit_errors'], $_SESSION['deposit_input']);

        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/deposit.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Xử lý form nạp tiền, tạo giao dịch và chuyển đến trang hiển thị thông tin thanh toán.
     * URL: /payment/generate-payment (POST)
     */
    public function generate_payment() {
        $this->requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('payment/deposit'));
        }
        
        $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT);
        $gatewayName = sanitize_input($_POST['gateway'] ?? '');
        $_SESSION['deposit_input'] = $_POST;

        if ($amount === false || $amount < 10000) { 
            $_SESSION['deposit_errors']['amount'] = 'Số tiền nạp không hợp lệ (tối thiểu là ' . format_currency(10000) . ').'; 
            redirect(base_url('payment/deposit')); 
            return; 
        }
        if (empty($gatewayName)) { 
            $_SESSION['deposit_errors']['gateway'] = 'Vui lòng chọn cổng nạp tiền.'; 
            redirect(base_url('payment/deposit')); 
            return; 
        }

        $randomContent = "NAPTIEN " . strtoupper(bin2hex(random_bytes(5)));

        $txnData = [ 
            'user_id' => getCurrentUser('id'), 
            'transaction_type' => TRANSACTION_TYPE_DEPOSIT, 
            'amount' => $amount, 
            'payment_method' => $gatewayName, 
            'status' => TRANSACTION_STATUS_PENDING, 
            'description' => $randomContent 
        ];
        $localTransactionId = $this->transactionModel->createTransaction($txnData);

        if (!$localTransactionId) { 
            set_flash_message('global_error', 'Lỗi hệ thống: không thể tạo giao dịch. Vui lòng thử lại.', MSG_ERROR); 
            redirect(base_url('payment/deposit')); 
            return; 
        }

        try {
            $paymentGateway = PaymentGatewayFactory::create($gatewayName);
            $paymentData = [
                'order_id' => $localTransactionId, 
                'amount' => $amount, 
                'description' => $randomContent,
                'return_url' => base_url('payment/deposit-result/success'),
                'cancel_url' => base_url('payment/deposit-result/cancel'),
            ];
            
            $paymentInfo = $paymentGateway->createPaymentRequest($paymentData);
            
            unset($_SESSION['deposit_errors'], $_SESSION['deposit_input']);

            // Nếu gateway trả về qr_code_url, ưu tiên hiển thị trang QR
            if (isset($paymentInfo['qr_code_url']) && !empty($paymentInfo['qr_code_url'])) {
                $_SESSION['payment_qr_info'] = $paymentInfo;
                redirect(base_url('payment/display-qr'));
            } 
            // Nếu không có QR nhưng là cổng thủ công (trả về thông tin hướng dẫn)
            elseif (isset($paymentInfo['is_manual']) && $paymentInfo['is_manual']) {
                $_SESSION['payment_manual_info'] = $paymentInfo;
                redirect(base_url('payment/display-manual-instructions'));
            } else {
                 throw new Exception("Cổng thanh toán không trả về thông tin thanh toán hợp lệ.");
            }

        } catch (Exception $e) {
            set_flash_message('global_error', 'Lỗi khi tạo mã thanh toán: ' . $e->getMessage(), MSG_ERROR);
            redirect(base_url('payment/deposit'));
        }
    }

    /**
     * Hiển thị trang QR Code (cho cổng tự động và bán tự động).
     */
    public function display_qr() {
        $this->requireLogin();
        if (!isset($_SESSION['payment_qr_info'])) {
            redirect(base_url('payment/deposit'));
        }
        $page_title = "Quét Mã Để Thanh Toán";
        $data['payment_info'] = $_SESSION['payment_qr_info'];
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/display-qr.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Hiển thị trang hướng dẫn chuyển khoản (cho cổng thủ công không có QR).
     */
    public function display_manual_instructions() {
        $this->requireLogin();
        if (!isset($_SESSION['payment_manual_info'])) {
            redirect(base_url('payment/deposit'));
        }
        $page_title = "Hướng Dẫn Nạp Tiền Thủ Công";
        $data['payment_info'] = $_SESSION['payment_manual_info'];
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/display-manual-instructions.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Hiển thị kết quả sau khi người dùng được redirect về từ cổng thanh toán.
     */
    public function deposit_result($status = 'cancel') {
        $this->requireLogin();
        if ($status === 'success') {
             set_flash_message('global_info', 'Giao dịch của bạn đang được xử lý. Số dư sẽ được cập nhật sau ít phút khi hệ thống nhận được xác nhận.', MSG_INFO);
        } else {
             set_flash_message('global_warning', 'Giao dịch đã bị hủy hoặc không thành công.', MSG_WARNING);
        }
        unset($_SESSION['payment_qr_info'], $_SESSION['payment_manual_info']);
        redirect(base_url('user/transaction-history'));
    }

    /**
     * Xử lý webhook từ cổng thanh toán.
     * URL: /webhook.php?gateway={TênCổng}
     */
    public function webhook($gatewayName) {
        $gatewayName = ucfirst(strtolower($gatewayName));
        write_log("Webhook received for gateway: {$gatewayName}", "WEBHOOK");
        
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            write_log("Webhook Error: Invalid JSON body.", "WEBHOOK_ERROR");
            http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); return;
        }
        
        write_log("Webhook Data for {$gatewayName}: " . json_encode($data), "WEBHOOK");
        
        try {
            $paymentGateway = PaymentGatewayFactory::create($gatewayName);
            
            if ($gatewayName === 'PayOS') {
                if (!isset($data['data']['description']) || !isset($data['data']['amount']) || !isset($data['data']['orderCode'])) {
                    throw new Exception("Webhook PayOS thiếu dữ liệu.");
                }
                $signature = $_SERVER['HTTP_PAYOS_SIGNATURE'] ?? '';
                if (!$paymentGateway->verifyWebhookSignature($data['data'], $signature)) {
                     throw new Exception("Webhook PayOS có chữ ký không hợp lệ.");
                }

                $content = $data['data']['description'];
                $amount = (float)$data['data']['amount'];
                $transactionId = (int)$data['data']['orderCode'];
                
                $transaction = $this->transactionModel->getTransactionById($transactionId);

                if ($transaction && $transaction['status'] === TRANSACTION_STATUS_PENDING) {
                    if (abs((float)$transaction['amount'] - $amount) < 1) { 
                        $this->userModel->updateUserBalance($transaction['user_id'], $transaction['amount'], 'add');
                        $this->transactionModel->updateTransactionStatus($transaction['id'], TRANSACTION_STATUS_COMPLETED, $data['data']['paymentLinkId'] ?? null);
                        write_log("Webhook SUCCESS for {$gatewayName}: Cập nhật thành công giao dịch ID {$transaction['id']}", "WEBHOOK_SUCCESS");
                    } else {
                        $this->transactionModel->updateTransactionStatus($transaction['id'], TRANSACTION_STATUS_FAILED, 'WRONG_AMOUNT');
                        write_log("Webhook MISMATCH for {$gatewayName}: Số tiền không khớp cho GD ID {$transaction['id']}.", "WEBHOOK_ERROR");
                    }
                } else {
                    write_log("Webhook NOT FOUND or ALREADY PROCESSED for {$gatewayName}: Không tìm thấy giao dịch pending với ID '{$transactionId}'", "WEBHOOK_WARNING");
                }
            }
            
            http_response_code(200); echo json_encode(['success' => true]);

        } catch (Exception $e) {
            write_log("Webhook Exception for {$gatewayName}: " . $e->getMessage(), "WEBHOOK_ERROR");
            http_response_code(400); echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>