<?php
// /views/templates/navbar.php
if (!defined('BASE_URL')) {
    // Điều này không nên xảy ra nếu config được nạp đúng cách từ index.php
    // Đây chỉ là fallback phòng trường hợp file được gọi trực tiếp (không khuyến khích)
    $fallback_base_path = rtrim(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/');
    if (file_exists(dirname(dirname(dirname(__FILE__))) . '/config/config.php')) {
         require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
    } else {
        // Nếu không có BASE_URL, việc tạo link sẽ rất khó khăn
        // define('BASE_URL', $fallback_base_path . '/public/'); // Cần cẩn thận với đường dẫn này
    }
}
if (!function_exists('isLoggedIn')) { // Kiểm tra một hàm trong functions.php
    if (file_exists(dirname(dirname(dirname(__FILE__))) . '/includes/functions.php')) {
        require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
    }
}


// Lấy số lượng item trong giỏ hàng từ session (vẫn giữ lại nếu bạn dùng CartModel cho mục đích khác)
// Tuy nhiên, với luồng "Mua Ngay", navbar có thể không cần hiển thị số lượng giỏ hàng nữa.
// Nếu bạn bỏ hoàn toàn giỏ hàng, có thể xóa biến này và phần hiển thị của nó.
$cart_item_count = isset($_SESSION['cart_item_count']) ? (int)$_SESSION['cart_item_count'] : 0;

// Xác định active link
$current_request_uri_for_nav = $_SERVER['REQUEST_URI'] ?? '';
$base_path_for_nav = rtrim(parse_url(BASE_URL, PHP_URL_PATH), '/'); // Ví dụ: /bulu-monster-shop/public

/**
 * Kiểm tra xem một link có active không dựa trên segment URL.
 * @param string $link_path_segment Đoạn path của link (ví dụ: 'product/list', 'user/profile')
 * @return bool
 */
if (!function_exists('isActive')) { // Định nghĩa hàm nếu chưa có (tránh lỗi khi include nhiều lần)
    function isActive($link_path_segment) {
        global $current_request_uri_for_nav, $base_path_for_nav;
        
        // Path đầy đủ của link cần so sánh (sau domain, bao gồm base_path)
        $full_link_path_to_compare = $base_path_for_nav . '/' . ltrim($link_path_segment, '/');
        // Nếu link_path_segment rỗng (trang chủ), chuẩn hóa nó thành /
        if (empty($link_path_segment) || $link_path_segment === '/') {
            $full_link_path_to_compare = rtrim($base_path_for_nav, '/') . '/';
        } else {
            $full_link_path_to_compare = rtrim($base_path_for_nav, '/') . '/' . trim($link_path_segment, '/');
        }
        
        // Lấy phần path của URL hiện tại (bỏ query string)
        $current_path_only = rtrim(parse_url($current_request_uri_for_nav, PHP_URL_PATH), '/');

        // So sánh chính xác path (sau khi đã chuẩn hóa)
        if ($full_link_path_to_compare === $current_path_only) {
            return true;
        }

        // Xử lý trường hợp trang chủ đặc biệt (khi $current_path_only là base_path)
        if (($link_path_segment === '' || $link_path_segment === 'index.php') && $current_path_only === $base_path_for_nav) {
            return true;
        }

        // Cho trường hợp như product/show/123 sẽ active link product/list (hoặc product/show)
        // So sánh xem path hiện tại có *bắt đầu bằng* path của link không (nếu link không phải trang chủ)
        if (!empty($link_path_segment) && $link_path_segment !== '/' && $link_path_segment !== 'index.php') {
            // Thêm dấu / vào cuối để đảm bảo so sánh đúng thư mục cha
            $full_link_path_to_compare_as_parent = rtrim($full_link_path_to_compare, '/') . '/';
            $current_path_only_as_child = rtrim($current_path_only, '/') . '/';
            if (strpos($current_path_only_as_child, $full_link_path_to_compare_as_parent) === 0) {
                return true;
            }
        }
        return false;
    }
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo base_url(); ?>"><?php echo htmlspecialchars(SITE_NAME); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('') || isActive('index.php') ? 'active' : ''; ?>" aria-current="page" href="<?php echo base_url(); ?>">Trang Chủ</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('product/list') ? 'active' : ''; ?>" href="<?php echo base_url('product/list'); ?>">Sản Phẩm</a>
                </li>
                <?php /* Link Giỏ Hàng đã được loại bỏ do chuyển sang luồng "Mua Ngay"
                <li class="nav-item">
                    <a class="nav-link <?php echo isActive('cart') ? 'active' : ''; ?>" href="<?php echo base_url('cart'); ?>">
                        Giỏ Hàng
                        <span class="badge bg-danger rounded-pill" id="cart-item-count-navbar"><?php echo $cart_item_count; ?></span>
                    </a>
                </li>
                */ ?>
                 <li class="nav-item">
                    <a class="nav-link <?php echo isActive('payment/deposit') ? 'active' : ''; ?>" href="<?php echo base_url('payment/deposit'); ?>">Nạp Tiền</a>
                </li>
            </ul>
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo (isActive('user/profile') || isActive('user/order-history') || isActive('user/transaction-history')) ? 'active' : ''; ?>" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Chào, <?php echo htmlspecialchars(getCurrentUser('username')); ?>
                            (<?php echo format_currency(getCurrentUser('balance') ?: 0); ?>)
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item <?php echo isActive('user/profile') ? 'active' : ''; ?>" href="<?php echo base_url('user/profile'); ?>">Thông Tin Cá Nhân</a></li>
                            <li><a class="dropdown-item <?php echo isActive('user/order-history') ? 'active' : ''; ?>" href="<?php echo base_url('user/order-history'); ?>">Đơn Hàng Đã Mua</a></li>
                            <li><a class="dropdown-item <?php echo isActive('user/transaction-history') ? 'active' : ''; ?>" href="<?php echo base_url('user/transaction-history'); ?>">Lịch Sử Giao Dịch Chung</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo base_url('admin/dashboard'); ?>" target="_blank">Trang Quản Trị</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo base_url('user/logout'); ?>">Đăng Xuất</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('user/login') ? 'active' : ''; ?>" href="<?php echo base_url('user/login'); ?>">Đăng Nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo isActive('user/register') ? 'active' : ''; ?>" href="<?php echo base_url('user/register'); ?>">Đăng Ký</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>