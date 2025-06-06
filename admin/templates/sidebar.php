<?php
// /admin/templates/sidebar.php
if (!defined('BASE_URL')) { /* ... */ }

$current_admin_module_from_url = '';
// Lấy module hiện tại từ $url_parts (biến này được tạo trong public/index.php)
// Tuy nhiên, sidebar.php được include từ header.php, header.php được include từ các action của controller.
// Cách tốt hơn là controller sẽ truyền module hiện tại vào view, hoặc chúng ta phân tích lại $_GET['url'] ở đây.

// Phân tích lại $_GET['url'] để xác định module hiện tại cho sidebar
$admin_sidebar_url_parts = [];
if (isset($_GET['url'])) {
    $admin_sidebar_url_parts = array_filter(explode('/', rtrim($_GET['url'], '/')));
}

if (isset($admin_sidebar_url_parts[0]) && strtolower($admin_sidebar_url_parts[0]) === 'admin') {
    $current_admin_module_from_url = $admin_sidebar_url_parts[1] ?? 'dashboard'; // module là phần tử thứ 2
} else {
    // Nếu không phải trang admin, hoặc URL không có dạng /admin/module
    $current_admin_module_from_url = 'dashboard'; // Mặc định
}


if (!function_exists('is_admin_module_active_for_sidebar')) { // Đặt tên hàm khác để tránh xung đột
    function is_admin_module_active_for_sidebar($module_name_check) {
        global $current_admin_module_from_url;
        if (is_array($module_name_check)) {
            return in_array($current_admin_module_from_url, $module_name_check);
        }
        return $current_admin_module_from_url === $module_name_check;
    }
}
?>
<nav class="admin-sidebar">
    <div class="text-center mb-3">
        <a href="<?php echo base_url('admin'); // Link về DashboardController->index() ?>" class="text-white text-decoration-none">
            <h4 class="mb-0 p-2"><?php echo htmlspecialchars(SITE_NAME); ?></h4>
            <p class="mb-0 small">Trang Quản Trị</p>
        </a>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('dashboard') ? 'active' : ''; ?>" href="<?php echo base_url('admin'); // Hoặc base_url('admin/dashboard') nếu router của bạn xử lý tốt hơn ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('product') ? 'active' : ''; ?>" href="<?php echo base_url('admin/product'); ?>">
                <i class="fas fa-box-open"></i> Quản lý Loại Sản phẩm
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('game-code') || is_admin_module_active_for_sidebar('manage-recovery-codes') ? 'active' : ''; ?>" href="<?php echo base_url('admin/game-code'); ?>">
                <i class="fas fa-archive"></i> Quản Lý Kho Mã Game
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('user') ? 'active' : ''; ?>" href="<?php echo base_url('admin/user'); ?>">
                <i class="fas fa-users"></i> Quản lý Người dùng
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('transaction') ? 'active' : ''; ?>" href="<?php echo base_url('admin/transaction'); ?>">
                <i class="fas fa-exchange-alt"></i> Quản lý Giao dịch
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('payment-gateway') ? 'active' : ''; ?>" href="<?php echo base_url('admin/payment-gateway'); ?>">
                <i class="fas fa-credit-card"></i> Quản lý Cổng Nạp
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('seo-setting') ? 'active' : ''; ?>" href="<?php echo base_url('admin/seo-setting'); ?>">
                <i class="fas fa-search-dollar"></i> Cài đặt SEO
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('statistic') ? 'active' : ''; ?>" href="<?php echo base_url('admin/statistic'); ?>">
                <i class="fas fa-chart-bar"></i> Thống kê
            </a>
        </li>
         <li class="nav-item">
            <a class="nav-link <?php echo is_admin_module_active_for_sidebar('password-reset-token') ? 'active' : ''; ?>" href="<?php echo base_url('admin/password-reset-token'); ?>">
                <i class="fas fa-user-lock"></i> Quản lý Token Reset Pass
            </a>
        </li>
    </ul>
</nav>