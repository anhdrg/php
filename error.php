<?php
// /public/error.php
// Đảm bảo BASE_URL đã được định nghĩa nếu bạn muốn dùng nó ở đây
// Ví dụ: để link về trang chủ hoặc load CSS/JS
if (!defined('BASE_URL')) {
    // Cố gắng định nghĩa BASE_URL nếu chưa có (trường hợp truy cập trực tiếp file error.php)
    // Đây là giải pháp tạm, tốt nhất là luôn đi qua index.php
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Giả sử cấu trúc thư mục của bạn là project-root/public
    // Và error.php nằm trong public
    // Cần điều chỉnh cho phù hợp
    $script_name_parts = explode('/', $_SERVER['SCRIPT_NAME']);
    $base_path_array = array_slice($script_name_parts, 0, count($script_name_parts) - 1); // Loại bỏ error.php
    $base_path = implode('/', $base_path_array) . '/';
    define('BASE_URL', $protocol . $host . $base_path);
}
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'My Website');
}

// Lấy thông điệp lỗi nếu có, nếu không thì dùng mặc định
$error_message = isset($message) ? htmlspecialchars($message) : "Trang bạn yêu cầu không được tìm thấy hoặc đã bị di chuyển.";

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi 404 - Không tìm thấy trang | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; background-color: #f8f9fa; text-align: center; }
        .error-container { max-width: 600px; padding: 20px; }
        .error-code { font-size: 6rem; font-weight: bold; color: #dc3545; }
        .error-message { font-size: 1.5rem; margin-top: 0; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <h1 class="error-message"><?php echo $error_message; ?></h1>
        <p>Rất tiếc, chúng tôi không thể tìm thấy trang bạn đang tìm kiếm.</p>
        <p>Có thể trang đã bị xóa, đổi tên hoặc tạm thời không có sẵn.</p>
        <a href="<?php echo rtrim(BASE_URL, '/'); ?>/" class="btn btn-primary mt-3">Quay về Trang Chủ</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
