<?php
// /public/index.php

// Nạp file cấu hình và các hằng số TRƯỚC TIÊN
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php'; // session_start() đã được gọi trong config.php

// Autoloader cho tất cả các class
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

// Làm mới thông tin người dùng trong session nếu đã đăng nhập
if (isLoggedIn()) {
    if (class_exists('UserModel')) {
        $userModelInstance = new UserModel();
        $currentUserIdInSession = $_SESSION['user_id'] ?? null;
        if ($currentUserIdInSession) {
            $freshUserData = $userModelInstance->findUserById($currentUserIdInSession);
            if ($freshUserData) {
                $_SESSION['user_data'] = $freshUserData;
                $_SESSION['user_id'] = $freshUserData['id']; 
                $_SESSION['user_role'] = $freshUserData['role'];
            } else {
                write_log("User ID {$currentUserIdInSession} trong session nhưng không tìm thấy trong DB. Đang xóa session user.", "WARNING");
                unset($_SESSION['user_id']); unset($_SESSION['user_role']); unset($_SESSION['user_data']); unset($_SESSION['cart_item_count']);
            }
        }
    } else {
        write_log("UserModel class không tồn tại khi làm mới session user.", "ERROR");
    }
}

// --- ROUTING LOGIC ---
$request_uri = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url_parts = array_filter(explode('/', $request_uri)); 

$controllerName = '';
$actionName = 'index'; 
$params = [];

$controllerNameSegment = array_shift($url_parts) ?? 'product'; 

if (strtolower($controllerNameSegment) === 'admin') {
    // --- XỬ LÝ ROUTE ADMIN ---
    $adminModuleSegment = array_shift($url_parts) ?? 'dashboard'; 
    $adminActionSegment = array_shift($url_parts) ?? 'index';   
    $params = $url_parts; 

    switch (strtolower($adminModuleSegment)) {
        case 'dashboard':
        case '': 
            $controllerName = 'DashboardController';
            break;
        case 'product': 
            $controllerName = 'AdminProductController';
            break;
        case 'user':    
            $controllerName = 'AdminUserController';
            break;
        case 'transaction': 
            $controllerName = 'TransactionController'; 
            break;
        case 'game-code': 
        case 'manage-recovery-codes': 
            $controllerName = 'GameCodeController';
            if ($adminModuleSegment === 'manage-recovery-codes' && $adminActionSegment === 'index') {
                 // No change needed, $adminActionSegment is already 'index'
            }
            break;
        case 'payment-gateway': 
            $controllerName = 'PaymentGatewayController';
            break;
        case 'seo-setting': 
            $controllerName = 'SeoSettingController';
            break;
        case 'statistic': 
            $controllerName = 'StatisticController';
            break;
        case 'password-reset-token': 
            $controllerName = 'PasswordResetTokenController';
            break;
        case 'media': // <<< THÊM CASE NÀY CHO MEDIA LIBRARY
            $controllerName = 'AdminMediaController';
            // $adminActionSegment sẽ là 'browse' từ URL admin/media/browse
            break;
        default:
            write_log("404 - Admin module không xác định: " . htmlspecialchars($adminModuleSegment), "ERROR");
            display_404_page("Module quản trị '" . htmlspecialchars($adminModuleSegment) . "' không tồn tại.");
            exit; 
    }
    
    $actionName = str_replace('-', '_', $adminActionSegment);
    $controllerFile = PROJECT_ROOT . '/controllers/Admin/' . $controllerName . '.php';

} else {
    // --- XỬ LÝ ROUTE FRONTEND ---
    $controllerName = ucfirst($controllerNameSegment) . 'Controller';
    $actionNameSegment = array_shift($url_parts) ?? 'index';
    $actionName = str_replace('-', '_', $actionNameSegment);
    $params = $url_parts;
    $controllerFile = PROJECT_ROOT . '/controllers/' . $controllerName . '.php';
}

// --- GỌI CONTROLLER VÀ ACTION ---
if (file_exists($controllerFile)) {
    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $actionName)) {
            call_user_func_array([$controller, $actionName], $params);
        } else {
            write_log("404 - Action '{$actionName}' không tìm thấy trong controller '{$controllerName}'. URI: " . ($request_uri ?? ''), "ERROR");
            display_404_page("Action '{$actionName}' không tìm thấy trong controller '{$controllerName}'.");
        }
    } else {
        write_log("404 - Class '{$controllerName}' không định nghĩa (file {$controllerFile}). URI: " . ($request_uri ?? ''), "ERROR");
        display_404_page("Controller class '{$controllerName}' không được định nghĩa.");
    }
} else {
    if ($controllerNameSegment === 'product' && $actionName === 'index' && empty($request_uri) && !file_exists($controllerFile) ) {
         write_log("404 - Default ProductController.php không tìm thấy cho trang chủ. URI: " . ($request_uri ?? ''), "CRITICAL");
         display_404_page("Không thể tải trang chủ. Controller chính không tìm thấy.");
    } else {
        write_log("404 - Controller file '{$controllerName}.php' (dự kiến: {$controllerFile}) không tìm thấy. URI: " . ($request_uri ?? ''), "ERROR");
        display_404_page("Controller file '{$controllerName}.php' không tìm thấy.");
    }
}

/**
 * Hiển thị trang lỗi 404.
 */
function display_404_page($message = "Trang bạn yêu cầu không tồn tại.") { /* ... như cũ ... */ }
?>