<?php
// /controllers/PaymentController.php

class PaymentController {
    private $paymentGatewayModel;
    private $userModel;
    private $transactionModel;
    private $db;

    public function __construct() {
        if (class_exists('PaymentGatewayModel')) $this->paymentGatewayModel = new PaymentGatewayModel(); else die('FATAL ERROR: PaymentGatewayModel not found.');
        if (class_exists('UserModel')) $this->userModel = new UserModel(); else die('FATAL ERROR: UserModel not found.');
        if (class_exists('TransactionModel')) $this->transactionModel = new TransactionModel(); else die('FATAL ERROR: TransactionModel not found.');
        if (class_exists('Database')) $this->db = new Database(); else die('FATAL ERROR: Database class not found.');
    }

    private function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? base_url();
            set_flash_message('auth_error', 'Vui lòng đăng nhập để thực hiện chức năng này.', MSG_INFO);
            redirect(base_url('user/login'));
            exit;
        }
    }

    public function deposit() {
        $this->requireLogin();
        $page_name_for_seo = 'deposit';
        $page_title = 'Nạp Tiền Vào Tài Khoản';
        
        $activeGateways = $this->paymentGatewayModel->getAllGateways(true);
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

        $uniqueContent = generateRandomAlphanumericString(8);

        $txnData = [ 
            'user_id' => getCurrentUser('id'), 
            'transaction_type' => TRANSACTION_TYPE_DEPOSIT, 
            'amount' => $amount, 
            'payment_method' => $gatewayName, 
            'status' => TRANSACTION_STATUS_PENDING, 
            'description' => $uniqueContent 
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
                'description' => $uniqueContent,
                'return_url' => base_url('payment/deposit-result/success'),
                'cancel_url' => base_url('payment/deposit-result/cancel'),
            ];
            
            $paymentInfo = $paymentGateway->createPaymentRequest($paymentData);
            
            unset($_SESSION['deposit_errors'], $_SESSION['deposit_input']);

            if (isset($paymentInfo['qr_code_url']) && !empty($paymentInfo['qr_code_url'])) {
                $_SESSION['payment_qr_info'] = $paymentInfo;
                redirect(base_url('payment/display-qr'));
            } else {
                 throw new Exception("Cổng thanh toán không trả về thông tin thanh toán hợp lệ.");
            }

        } catch (Exception $e) {
            set_flash_message('global_error', 'Lỗi khi tạo mã thanh toán: ' . $e->getMessage(), MSG_ERROR);
            redirect(base_url('payment/deposit'));
        }
    }

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

    public function deposit_result($status = 'cancel') {
        $this->requireLogin();
        if ($status === 'success') {
             set_flash_message('global_info', 'Giao dịch của bạn đang được xử lý. Số dư sẽ được cập nhật sau ít phút khi hệ thống nhận được xác nhận.', MSG_INFO);
        } else {
             set_flash_message('global_warning', 'Giao dịch đã bị hủy hoặc không thành công.', MSG_WARNING);
        }
        unset($_SESSION['payment_qr_info']);
        redirect(base_url('user/transaction-history'));
    }

    /**
     * Xử lý webhook từ các cổng thanh toán.
     */
    public function webhook($gatewayName) {
        $gatewayName = ucfirst(strtolower($gatewayName));
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);
        
        write_log("[Webhook:{$gatewayName}] Received data: " . $body, "WEBHOOK");

        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400); echo json_encode(['error' => 'Invalid JSON']); return;
        }
        
        try {
            if ($gatewayName === 'Timo Bank') {
                $signatureFromHeader = $_SERVER['HTTP_X_TIMO_SIGNATURE'] ?? '';
                $webhookSecret = 'YOUR_WEBHOOK_SECRET_HERE'; // << NHỚ ĐẶT SECRET KEY CỦA BẠN VÀO ĐÂY

                $calculatedSignature = base64_encode(hash_hmac('sha256', $body, $webhookSecret, true));
                
                if (hash_equals($calculatedSignature, $signatureFromHeader) === false) {
                     throw new Exception("Webhook Timo Bank có chữ ký không hợp lệ.");
                }

                $amount = $data['amount'] ?? 0;
                $fullDescription = $data['full_description'] ?? null;
                $transactionDate = $data['transaction_date'] ?? null;

                if ($amount <= 0 || empty($fullDescription)) {
                     throw new Exception("Webhook Timo Bank thiếu dữ liệu amount hoặc full_description.");
                }
                
                // Ghi nhận vào bảng timo_transactions
                $this->db->query("INSERT INTO timo_transactions (amount, content, transaction_date, raw_payload) 
                                 VALUES (:amount, :content, :transaction_date, :raw_payload)");
                $this->db->bind(':amount', $amount);
                $this->db->bind(':content', $fullDescription);
                $this->db->bind(':transaction_date', $transactionDate);
                $this->db->bind(':raw_payload', $body);
                $this->db->execute();
                
                write_log("Webhook SUCCESS for Timo: Đã ghi nhận giao dịch từ email vào bảng timo_transactions.", "WEBHOOK_SUCCESS");

                // --- TẠM THỜI CHÚNG TA CHỈ GHI NHẬN, KHÔNG TỰ ĐỘNG CỘNG TIỀN ---
                // Sau này, bạn có thể thêm logic đối chiếu và cộng tiền tự động ở đây.
            }
            // Thêm else if cho các cổng khác như PayOS ở đây nếu cần sau này
            
            http_response_code(200); 
            echo json_encode(['success' => true, 'message' => 'Webhook received.']);

        } catch (Exception $e) {
            write_log("Webhook Exception for {$gatewayName}: " . $e->getMessage(), "WEBHOOK_ERROR");
            http_response_code(400); 
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}
?>