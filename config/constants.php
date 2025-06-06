<?php
// /config/constants.php

// --- Cấu hình vai trò người dùng ---
define('ROLE_USER', 'user');
define('ROLE_ADMIN', 'admin');

// --- Cấu hình trạng thái giao dịch ---
define('TRANSACTION_STATUS_PENDING', 'pending');
define('TRANSACTION_STATUS_COMPLETED', 'completed');
define('TRANSACTION_STATUS_FAILED', 'failed');
define('TRANSACTION_STATUS_CANCELLED', 'cancelled');
define('TRANSACTION_STATUS_REFUNDED', 'refunded');

// --- Cấu hình loại giao dịch ---
define('TRANSACTION_TYPE_PURCHASE', 'purchase');
define('TRANSACTION_TYPE_DEPOSIT', 'deposit');

// --- Cấu hình phương thức thanh toán ---
define('PAYMENT_METHOD_BALANCE', 'system_balance');
define('PAYMENT_METHOD_PAYOS', 'PayOS');
define('PAYMENT_METHOD_SEPAY', 'SEPay');
define('PAYMENT_METHOD_ADMIN', 'admin_adjustment');

// --- Cấu hình chung cho hiển thị & phân trang ---
define('DEFAULT_ITEMS_PER_PAGE', 8);
define('ADMIN_DEFAULT_ITEMS_PER_PAGE', 10);

// --- Các loại thông báo Flash Message ---
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'danger');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// --- Cấu hình cho việc upload file ---
define('MEDIA_LIBRARY_UPLOAD_PATH', 'assets/media_library/'); 
define('PRODUCT_IMAGE_UPLOAD_PATH', 'assets/images/products/');
define('MAX_UPLOAD_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// --- Cấu hình thời gian (ví dụ cho token, session) ---
define('PASSWORD_RESET_TOKEN_DURATION', 3600); // 1 giờ
define('SESSION_COOKIE_LIFETIME_REMEMBER_ME', 86400 * 30); // 30 ngày
?>