<?php
// /api/webhook-handler.php
// Endpoint chung để nhận webhook từ tất cả các cổng thanh toán.
// URL sẽ có dạng: yourdomain.com/api/webhook-handler.php?gateway=payos
// hoặc yourdomain.com/api/webhook-handler.php?gateway=sepay

require_once dirname(__DIR__) . '/config/config.php';
require_once PROJECT_ROOT . '/config/constants.php';
require_once PROJECT_ROOT . '/includes/functions.php';

// Autoloader
spl_autoload_register(function ($className) {
    $paths = [
        PROJECT_ROOT . '/models/',
        PROJECT_ROOT . '/core/', // Cho PaymentGatewayFactory
        PROJECT_ROOT . '/gateways/', // Cho các class gateway cụ thể
        PROJECT_ROOT . '/interfaces/' // Cho PaymentGatewayInterface
    ];
    foreach ($paths as $path) {
        $file = $path . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$gatewayIdentifier = sanitize_input($_GET['gateway'] ?? ''); // Lấy tên cổng từ URL

if (empty($gatewayIdentifier)) {
    write_log("[Webhook Handler] Thiếu tham số 'gateway' trong URL.", "ERROR");
    http_response_code(400);
    echo json_encode(["error" => "Gateway parameter missing."]);
    exit;
}

$paymentGatewayModel = new PaymentGatewayModel();
$gatewayDetails = $paymentGatewayModel->getGatewayByName(strtoupper($gatewayIdentifier)); // Đảm bảo tên cổng viết hoa

if (!$gatewayDetails) {
    write_log("[Webhook Handler] Cổng thanh toán không hợp lệ: " . $gatewayIdentifier, "ERROR");
    http_response_code(404);
    echo json_encode(["error" => "Invalid payment gateway."]);
    exit;
}

// Lấy cấu hình cho cổng
$gatewayConfig = [
    'client_id'     => $gatewayDetails['client_id'],
    'api_key'       => $gatewayDetails['api_key'],
    'checksum_key'  => $gatewayDetails['checksum_key'],
    'merchant_id'   => $gatewayDetails['merchant_id'],
    'name'          => $gatewayDetails['name']
    // Thêm các cấu hình khác nếu cổng cần
];

$gatewayInstance = PaymentGatewayFactory::create($gatewayDetails['name'], $gatewayConfig);

if (!$gatewayInstance) {
    write_log("[Webhook Handler] Không thể tạo instance cho cổng: " . $gatewayDetails['name'], "ERROR");
    http_response_code(500);
    echo json_encode(["error" => "Could not initialize gateway handler."]);
    exit;
}

// Lấy dữ liệu webhook (thường là JSON trong request body hoặc POST data)
$requestBody = file_get_contents('php://input');
$webhookData = json_decode($requestBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    // Nếu không phải JSON, thử lấy từ POST (cho mock page)
    if (!empty($_POST)) {
        $webhookData = $_POST;
         write_log("[Webhook Handler - {$gatewayDetails['name']}] Received POST data: " . json_encode($webhookData), "INFO");
    } else {
        write_log("[Webhook Handler - {$gatewayDetails['name']}] Dữ liệu webhook không phải JSON hợp lệ: " . $requestBody, "ERROR");
        http_response_code(400); echo json_encode(["error" => "Invalid JSON payload."]); exit;
    }
} else {
    write_log("[Webhook Handler - {$gatewayDetails['name']}] Received JSON data: " . $requestBody, "INFO");
}


$serverHeaders = getallheaders(); // Lấy tất cả headers

// Gọi phương thức xử lý webhook của instance cổng thanh toán
$processedResult = $gatewayInstance->handleWebhook($webhookData, $serverHeaders);

if (!($processedResult['is_signature_valid'] ?? false)) {
    write_log("[Webhook Handler - {$gatewayDetails['name']}] Xác thực chữ ký thất bại. OrderCode: " . ($processedResult['order_code'] ?? 'N/A'), "ERROR");
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => $processedResult['message'] ?? "Invalid signature or webhook data."]);
    exit;
}

// Xử lý logic nghiệp vụ dựa trên kết quả từ handleWebhook
$transactionModel = new TransactionModel();
$userModel = new UserModel();

$orderCode = $processedResult['order_code'] ?? null; // Mã đơn hàng của hệ thống bạn
$gatewayStatus = $processedResult['status'] ?? TRANSACTION_STATUS_FAILED; // Trạng thái đã được ánh xạ
$gatewayTxnId = $processedResult['gateway_txn_id'] ?? null;
$amountPaid = $processedResult['amount_paid'] ?? 0;

if (empty($orderCode)) {
    write_log("[Webhook Handler - {$gatewayDetails['name']}] Thiếu order_code trong dữ liệu đã xử lý từ webhook.", "ERROR");
    http_response_code(400); echo json_encode(["error" => "Processed data missing order_code."]); exit;
}

// Tìm giao dịch bằng order_code (cần đảm bảo order_code này là duy nhất và có thể tìm được)
// Hiện tại, orderCode của chúng ta là 'DEP-USERID-TIME-RAND', không phải ID giao dịch.
// Bạn cần một cách để map orderCode này về localTransactionId.
// Ví dụ: lưu orderCode vào một cột riêng trong bảng transactions khi tạo.
// Hoặc, nếu localTransactionId được truyền qua extraData và được gateway trả về trong webhook.

// *** GIẢ SỬ: $orderCode CHÍNH LÀ localTransactionId ĐỂ ĐƠN GIẢN CHO MOCK ***
// HOẶC, nếu mock page gửi localTransactionId dưới một tên khác trong webhookData
$localTransactionIdToFind = $webhookData['localTransactionId'] ?? ($webhookData['transactionId'] ?? null);
if ($gatewayDetails['name'] == 'PayOS' && isset($webhookData['data']['orderCode'])){ // PayOS có thể gửi orderCode trong data
    // PayOS thường trả về orderCode là mã của bạn
    // $transaction = $transactionModel->findTransactionBySystemOrderCode($webhookData['data']['orderCode']);
    // Nhưng để test với mock, chúng ta đang dùng $localTransactionIdToFind
}

$transaction = $localTransactionIdToFind ? $transactionModel->getTransactionById((int)$localTransactionIdToFind) : null;
// Nếu không tìm thấy bằng ID, thử tìm bằng order_code trong description (cách này không tốt)
if (!$transaction) {
     $this->db->query("SELECT * FROM transactions WHERE description LIKE :order_code_desc AND status = :pending_status LIMIT 1");
     $this->db->bind(':order_code_desc', "%Mã đơn: " . $orderCode . "%");
     $this->db->bind(':pending_status', TRANSACTION_STATUS_PENDING);
     $transaction = $this->db->single();
}


if (!$transaction) {
    write_log("[Webhook Handler - {$gatewayDetails['name']}] Không tìm thấy giao dịch với OrderCode: {$orderCode} hoặc LocalID: {$localTransactionIdToFind}", "ERROR");
    http_response_code(404); echo json_encode(["error" => "Transaction not found."]); exit;
}

if ($transaction['status'] === TRANSACTION_STATUS_PENDING) {
    if ($gatewayStatus === TRANSACTION_STATUS_COMPLETED) {
        // Kiểm tra số tiền có khớp không (quan trọng)
        if (abs((float)$transaction['amount'] - (float)$amountPaid) > 0.01 && $amountPaid > 0) { // Chỉ check nếu amountPaid > 0
            write_log("[Webhook - {$gatewayDetails['name']}] Sai lệch số tiền. OrderCode: {$orderCode}. DB: {$transaction['amount']}, Gateway: {$amountPaid}", "ERROR");
            $transactionModel->updateTransactionStatus($transaction['id'], TRANSACTION_STATUS_FAILED, $gatewayTxnId . " (AmountMismatch)");
            http_response_code(400); echo json_encode(["error" => "Amount mismatch."]); exit;
        }
        
        $updateSuccess = $transactionModel->updateTransactionStatus($transaction['id'], TRANSACTION_STATUS_COMPLETED, $gatewayTxnId);
        if ($updateSuccess) {
            if ($transaction['transaction_type'] === TRANSACTION_TYPE_DEPOSIT) {
                if ($userModel->updateUserBalance($transaction['user_id'], (float)$transaction['amount'], 'add')) {
                    write_log("[Webhook - {$gatewayDetails['name']}] Nạp tiền thành công. Giao dịch ID: {$transaction['id']}, UserID: {$transaction['user_id']}", "INFO");
                    // Gửi email thông báo nạp tiền thành công (nếu cần)
                } else {
                    write_log("[Webhook - {$gatewayDetails['name']}] LỖI NGHIÊM TRỌNG: Không thể cập nhật số dư UserID: {$transaction['user_id']} sau khi GD {$transaction['id']} thành công.", "CRITICAL");
                }
            }
            // Các logic khác nếu là 'purchase' (hiện tại không dùng cổng cho purchase)
        } else {
            write_log("[Webhook - {$gatewayDetails['name']}] Lỗi cập nhật trạng thái GD {$transaction['id']} thành COMPLETED.", "ERROR");
            http_response_code(500); echo json_encode(["error" => "Failed to update transaction."]); exit;
        }
    } elseif (in_array($gatewayStatus, [TRANSACTION_STATUS_FAILED, TRANSACTION_STATUS_CANCELLED])) {
        $transactionModel->updateTransactionStatus($transaction['id'], $gatewayStatus, $gatewayTxnId);
        write_log("[Webhook - {$gatewayDetails['name']}] Giao dịch {$transaction['id']} có trạng thái {$gatewayStatus} từ cổng.", "INFO");
    } else {
         write_log("[Webhook - {$gatewayDetails['name']}] Trạng thái không xác định '{$gatewayStatus}' cho GD {$transaction['id']}.", "WARNING");
    }
} elseif ($transaction['status'] === TRANSACTION_STATUS_COMPLETED) {
    write_log("[Webhook - {$gatewayDetails['name']}] Giao dịch {$transaction['id']} đã được xử lý (COMPLETED). Bỏ qua.", "INFO");
} else {
    write_log("[Webhook - {$gatewayDetails['name']}] Giao dịch {$transaction['id']} có trạng thái {$transaction['status']}, không phải PENDING. Bỏ qua.", "INFO");
}

http_response_code(200); // Luôn trả về 200 OK cho cổng thanh toán nếu đã xử lý (hoặc cố gắng xử lý)
echo json_encode(["success" => true, "message" => "Webhook received by handler."]);
?>