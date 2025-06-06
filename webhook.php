<?php
// /webhook.php
// Endpoint để nhận callback/webhook từ các dịch vụ bên ngoài (Google Apps Script, PayOS, etc.)

// Ghi lại yêu cầu đến để debug
$raw_request_for_log = "Webhook call to: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . " | Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'N/A');
// Không ghi body ở đây vì nó có thể chứa thông tin nhạy cảm ở dạng thô

// Nạp các file cấu hình và autoloader cần thiết
// Vì webhook.php nằm ở gốc, đường dẫn cần được điều chỉnh cho đúng
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

spl_autoload_register(function ($className) {
    $paths = [
        PROJECT_ROOT . '/controllers/',
        PROJECT_ROOT . '/controllers/Admin/',
        PROJECT_ROOT . '/models/',
        PROJECT_ROOT . '/core/',      
        PROJECT_ROOT . '/gateways/',  
        PROJECT_ROOT . '/interfaces/' 
    ];
    foreach ($paths as $path) {
        $file = rtrim($path, '/') . '/' . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});


// Phân tích URL để gọi đúng action
// URL sẽ có dạng: /webhook.php?gateway=Timo%20Bank
$gatewayName = $_GET['gateway'] ?? 'Unknown';

if ($gatewayName === 'Unknown') {
    http_response_code(400);
    echo json_encode(['error' => 'Gateway not specified.']);
    exit;
}

// Gọi đến PaymentController để xử lý
try {
    if (class_exists('PaymentController')) {
        $controller = new PaymentController();
        if(method_exists($controller, 'webhook')) {
             // Truyền tên cổng thanh toán vào phương thức webhook
             $controller->webhook($gatewayName);
        } else {
             throw new Exception("Phương thức webhook không tồn tại trong PaymentController.");
        }
    } else {
        throw new Exception("Class PaymentController không tồn tại.");
    }
} catch (Exception $e) {
    http_response_code(500);
    // Ghi log lỗi nghiêm trọng
    write_log("Lỗi nghiêm trọng trong webhook.php: " . $e->getMessage(), "WEBHOOK_ERROR");
    // Trả về một thông báo lỗi chung
    echo json_encode(['error' => 'Lỗi xử lý hệ thống webhook.']);
}
?>