<?php
// /admin/templates/header.php
if (!defined('BASE_URL')) {
    // Điều này không nên xảy ra nếu đi qua public/index.php -> AdminController
    // Tạm thời đặt để tránh lỗi nếu gọi trực tiếp (không khuyến khích)
    // Cần điều chỉnh đường dẫn cho chính xác nếu bạn có cấu trúc khác
    $configPath = dirname(dirname(dirname(__FILE__))) . '/config/config.php';
    if (file_exists($configPath)) {
        require_once $configPath;
    } else {
        die('Không tìm thấy file config.php');
    }
    $functionsPath = dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
     if (file_exists($functionsPath)) {
        require_once $functionsPath;
    } else {
        die('Không tìm thấy file functions.php');
    }
}

// Kiểm tra đăng nhập và quyền admin
if (!isAdmin()) {
    set_flash_message('admin_auth_error', 'Bạn không có quyền truy cập trang này.', MSG_ERROR);
    redirect(base_url('user/login')); // Hoặc trang lỗi riêng cho admin
    exit;
}

$admin_page_title = isset($admin_page_title) ? $admin_page_title . ' | Admin - ' . SITE_NAME : 'Admin Dashboard | ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($admin_page_title); ?></title>
    
    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="<?php echo asset_url('css/admin-custom.css'); ?>" rel="stylesheet"> 
    
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }
        .admin-wrapper {
            display: flex;
            flex: 1;
        }
        .admin-sidebar {
            width: 250px;
            background-color: #343a40; /* Dark background for sidebar */
            color: #fff;
            min-height: 100vh; /* Full height */
            padding-top: 1rem;
        }
        .admin-sidebar .nav-link {
            color: #adb5bd;
        }
        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #fff;
            background-color: #495057;
        }
        .admin-sidebar .nav-link .fas {
            margin-right: 0.5rem;
        }
        .admin-content {
            flex-grow: 1;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .admin-main-header {
            background-color: #ffffff;
            padding: 10px 20px;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="admin-wrapper">
    <?php include_once 'sidebar.php'; // Include thanh sidebar điều hướng của admin ?>
    
    <div class="admin-content-wrapper flex-grow-1 d-flex flex-column">
        <header class="admin-main-header shadow-sm">
            <h4 class="mb-0 page-identifier"><?php echo htmlspecialchars(isset($admin_current_page_title) ? $admin_current_page_title : 'Dashboard'); ?></h4>
            <div>
                <span class="me-2">Chào, <?php echo htmlspecialchars(getCurrentUser('username')); ?>!</span>
                <a href="<?php echo base_url(); ?>" target="_blank" class="btn btn-sm btn-outline-secondary me-2" title="Xem Trang Web">
                    <i class="fas fa-eye"></i> Xem Web
                </a>
                <a href="<?php echo base_url('user/logout'); ?>" class="btn btn-sm btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </header>

        <main class="admin-content">
            <?php
            // Hiển thị flash messages cho admin
            echo display_flash_message('admin_global_success');
            echo display_flash_message('admin_global_error');
            echo display_flash_message('admin_global_info');
            ?>
