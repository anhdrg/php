<?php
// /config/config.php

// Định nghĩa các hằng số cấu hình cho website
// **Cấu hình Database**
define('DB_HOST', 'localhost'); // Thường là localhost
define('DB_USERNAME', 'ysatdbhshosting_bulumonster'); // Thay bằng username CSDL của bạn
define('DB_PASSWORD', 'w7._yPqSTE[eicJ'); // Thay bằng password CSDL của bạn
define('DB_NAME', 'ysatdbhshosting_bulumonster');    // Thay bằng tên CSDL bạn đã tạo

// **Cấu hình URL gốc của trang web**
// Quan trọng: Thay đổi giá trị này cho phù hợp với môi trường của bạn
// Ví dụ: Nếu website của bạn chạy ở http://localhost/bulu-monster-shop/
// thì BASE_URL sẽ là 'http://localhost/bulu-monster-shop/'
// Nếu chạy trên domain thật: 'https://yourdomain.com/'
// Đảm bảo có dấu / ở cuối
define('BASE_URL', 'https://bulumonster.kiotweb.io.vn/'); // Sửa lại cho đúng với cấu trúc của bạn

// **Cấu hình PayOS**
define('PAYOS_CLIENT_ID', '72a4de6c-c7c9-49cc-8541-e167e17081e1');
define('PAYOS_API_KEY', '0f57a49e-f6f8-40f4-89be-bfdee20954da');
define('PAYOS_CHECKSUM_KEY', 'da7e92845c4ecd213249afd20245ce1b7c892455ef47f63e5253bd0c07dc77c6');
define('PAYOS_RETURN_URL', BASE_URL . 'checkout-success.php'); // Trang sau khi thanh toán thành công
define('PAYOS_CANCEL_URL', BASE_URL . 'checkout-cancel.php'); // Trang sau khi hủy thanh toán

// **Cấu hình SEPay**
define('SEPAY_API_KEY', 'HYP3BRM2Q1HREZVAZPWO6IBIL0JOUSKLR0NXPLC1TDYWEMIN5OFB7DTA4XEMVGGN');
// SEPay có thể cần thêm các cấu hình khác như Merchant ID, Return URL, Notify URL
// Ví dụ:
define('SEPAY_MERCHANT_ID', 'YOUR_SEPAY_MERCHANT_ID'); // Thay bằng Merchant ID của bạn
define('SEPAY_RETURN_URL', BASE_URL . 'sepay-return.php');
define('SEPAY_NOTIFY_URL', BASE_URL . '../api/sepay-webhook.php'); // Đường dẫn tới webhook

// **Cấu hình chung**
define('SITE_NAME', 'Bán tài khoản Bulu Monster');
define('SESSION_TIMEOUT', 3600); // Thời gian timeout session (ví dụ: 1 giờ)
define('DEBUG_MODE', true); // Bật/tắt chế độ debug (hiển thị lỗi chi tiết)

// Thiết lập múi giờ
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Bật hiển thị lỗi nếu ở chế độ DEBUG_MODE
if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Bắt đầu session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Đường dẫn tuyệt đối đến thư mục gốc của dự án
define('PROJECT_ROOT', dirname(__DIR__)); // Thư mục /project-root


?>
