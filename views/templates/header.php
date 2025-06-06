<?php
// /views/templates/header.php

// Đảm bảo các file cần thiết đã được nạp, đặc biệt là config và functions
// Thông thường, chúng đã được nạp bởi public/index.php hoặc controller
if (!defined('BASE_URL')) {
    // Đây là trường hợp dự phòng, không nên xảy ra trong luồng bình thường
    $baseConfigPath = dirname(dirname(dirname(__FILE__))) . '/config/config.php';
    if (file_exists($baseConfigPath)) require_once $baseConfigPath;
    else die('Config file not found in header template.');
}
if (!function_exists('asset_url')) {
    $baseFunctionsPath = dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
    if (file_exists($baseFunctionsPath)) require_once $baseFunctionsPath;
    else die('Functions file not found in header template.');
}

// --- SEO Logic ---
// Các giá trị SEO mặc định
$final_page_title = SITE_NAME; // Lấy từ config.php
$final_meta_description = "Chào mừng bạn đến với " . SITE_NAME . ". Nơi cung cấp các tài khoản game Bulu Monster chất lượng."; // Mô tả mặc định
$final_meta_keywords = "bulu monster, game account, " . strtolower(str_replace(' ', ', ', SITE_NAME)); // Từ khóa mặc định

// Biến $page_name_for_seo, $page_title, $meta_description, $meta_keywords
// nên được controller đặt và truyền vào thông qua mảng $data khi gọi view.
// Ví dụ: $data['page_name_for_seo'] = 'home';
//        $data['page_title'] = 'Trang Chủ Đặc Biệt'; // Ưu tiên hơn SEO từ DB nếu được đặt

// Ưu tiên 1: Các biến SEO được controller đặt trực tiếp (ví dụ cho trang chi tiết sản phẩm)
if (isset($page_title) && !empty($page_title)) {
    $final_page_title = htmlspecialchars($page_title) . ' | ' . SITE_NAME;
}
if (isset($meta_description) && !empty($meta_description)) {
    $final_meta_description = htmlspecialchars($meta_description);
}
if (isset($meta_keywords) && !empty($meta_keywords)) {
    $final_meta_keywords = htmlspecialchars($meta_keywords);
}

// Ưu tiên 2: Lấy từ CSDL nếu $page_name_for_seo được controller đặt và không có ghi đè trực tiếp mạnh hơn
// Biến $page_title, $meta_description, $meta_keywords ở trên có thể là giá trị mặc định của controller
// hoặc giá trị đã được xử lý động (ví dụ tên sản phẩm). Nếu SeoModel có giá trị, nó có thể ghi đè hoặc bổ sung.

if (isset($page_name_for_seo) && !empty($page_name_for_seo)) {
    // Chỉ khởi tạo SeoModel nếu chưa được controller thực hiện
    if (!class_exists('SeoModel')) { // Kiểm tra class tồn tại trước khi dùng
        $seoModelPath = PROJECT_ROOT . '/models/SeoModel.php';
        if (file_exists($seoModelPath)) {
            require_once $seoModelPath;
        }
    }

    if (class_exists('SeoModel')) {
        $seoModelInstance = new SeoModel();
        $seoDataFromDb = $seoModelInstance->getSeoSettingsByPageName($page_name_for_seo);

        if ($seoDataFromDb) {
            // Nếu controller không đặt $page_title cụ thể, hoặc bạn muốn SEO từ DB luôn mạnh hơn
            if (!empty($seoDataFromDb['meta_title'])) {
                 // Nếu controller đã đặt $page_title (ví dụ tên sản phẩm), có thể không muốn ghi đè hoàn toàn
                 // mà chỉ dùng $seoDataFromDb['meta_title'] làm mẫu nếu $page_title chưa có.
                 // Quyết định này tùy thuộc vào logic bạn muốn.
                 // Ví dụ: nếu $page_title đã được controller đặt cho chi tiết sản phẩm, không ghi đè ở đây.
                 // Còn nếu là trang tĩnh như 'home', 'contact', thì dùng giá trị từ DB.
                if (!isset($page_title) || empty($page_title) || in_array($page_name_for_seo, ['home', 'product_list', 'login', 'register', 'cart', 'checkout'])) { // Các trang dùng title từ DB
                     $final_page_title = htmlspecialchars($seoDataFromDb['meta_title']) . ' | ' . SITE_NAME;
                }
            }
            if (!empty($seoDataFromDb['meta_description'])) {
                $final_meta_description = htmlspecialchars($seoDataFromDb['meta_description']);
            }
            if (!empty($seoDataFromDb['meta_keywords'])) {
                $final_meta_keywords = htmlspecialchars($seoDataFromDb['meta_keywords']);
            }
        }
    }
}
// --- End SEO Logic ---

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $final_page_title; ?></title>
    <meta name="description" content="<?php echo $final_meta_description; ?>">
    <meta name="keywords" content="<?php echo $final_meta_keywords; ?>">

    <link href="<?php echo asset_url('css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?php echo asset_url('css/custom.css'); ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <meta property="og:title" content="<?php echo $final_page_title; ?>" />
    <meta property="og:description" content="<?php echo $final_meta_description; ?>" />
    <meta property="og:type" content="website" /> 
    <?php // Lấy URL hiện tại một cách an toàn hơn
        $current_url_og = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    ?>
    <meta property="og:url" content="<?php echo htmlspecialchars($current_url_og); ?>" />
    <?php
        // Nên có một ảnh OG mặc định, và có thể ghi đè bởi controller cho các trang cụ thể (ví dụ ảnh sản phẩm)
        $og_image_url = isset($page_og_image) ? asset_url($page_og_image) : asset_url('images/og-default-image.jpg'); // Tạo file og-default-image.jpg
    ?>
    <meta property="og:image" content="<?php echo $og_image_url; ?>" />
    <meta property="og:site_name" content="<?php echo SITE_NAME; ?>" />


</head>
<body>

<?php include_once 'navbar.php'; // Include thanh điều hướng ?>

<div class="container mt-4 mb-4 main-container"> <?php
    // Hiển thị flash messages nếu có
    echo display_flash_message('global_success');
    echo display_flash_message('global_error');
    echo display_flash_message('global_info');
    echo display_flash_message('cart_success'); // Flash messages từ giỏ hàng
    echo display_flash_message('cart_error');
    echo display_flash_message('cart_info');
    echo display_flash_message('cart_updated');
    // Hiển thị các thông báo cập nhật item cụ thể (nếu có, từ CartModel->getCartItems)
    if (isset($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $key => $flash_msg_item) { // Sửa tên biến để tránh ghi đè
            if (strpos($key, 'cart_updated_item_') === 0) {
                echo display_flash_message($key);
            }
        }
    }
    echo display_flash_message('checkout_login_required');
    echo display_flash_message('checkout_error');
    echo display_flash_message('checkout_success');
    echo display_flash_message('checkout_info');
    echo display_flash_message('cart_empty');
    ?>