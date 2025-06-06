<?php
// /controllers/ProductController.php

class ProductController {
    private $productModel;

    public function __construct() {
        if (class_exists('ProductModel')) {
            $this->productModel = new ProductModel();
        } else {
            write_log("FATAL ERROR: Class ProductModel không tìm thấy trong ProductController.", "ERROR");
            die('Lỗi hệ thống: ProductModel không khả dụng.');
        }
    }

    /**
     * Hiển thị trang chủ với danh sách sản phẩm.
     * Mặc định là các sản phẩm được 'is_active' (Hiển thị trang chủ).
     */
    public function index() {
        $page_name_for_seo = 'home'; // Để header.php lấy meta tags từ CSDL cho trang chủ
        $page_title = "Trang Chủ"; // Tiêu đề mặc định nếu SEO không được đặt
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('DEFAULT_ITEMS_PER_PAGE') ? DEFAULT_ITEMS_PER_PAGE : 8;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $searchTerm = isset($_GET['search']) ? sanitize_input($_GET['search']) : null;
        $orderBy = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'p.created_at';
        $orderDir = isset($_GET['dir']) ? sanitize_input($_GET['dir']) : 'DESC';

        // Gọi phương thức mới đã được cập nhật trong ProductModel
        $products = $this->productModel->getActiveProductTypesWithStock($itemsPerPage, $offset, $searchTerm, $orderBy, $orderDir);
        $totalProducts = $this->productModel->getTotalActiveProductTypesCount($searchTerm);
        $totalPages = ceil($totalProducts / $itemsPerPage);

        $data = [
            'products' => $products,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'searchTerm' => $searchTerm,
            'orderBy' => $orderBy,
            'orderDir' => $orderDir,
            'is_product_list_page' => false // Đánh dấu đây là trang chủ, không phải trang /product/list
        ];
        
        // Các biến SEO và dữ liệu được truyền vào view
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/index.php'; // Trang chủ và trang danh sách sản phẩm có thể dùng chung view này
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Hiển thị trang danh sách tất cả sản phẩm.
     */
    public function list() {
        $page_name_for_seo = 'product_list';
        $page_title = "Danh Sách Sản Phẩm";
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('DEFAULT_ITEMS_PER_PAGE') ? DEFAULT_ITEMS_PER_PAGE : 8;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $searchTerm = isset($_GET['search']) ? sanitize_input($_GET['search']) : null;
        $orderBy = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'p.created_at';
        $orderDir = isset($_GET['dir']) ? sanitize_input($_GET['dir']) : 'DESC';

        // Gọi phương thức mới đã được cập nhật trong ProductModel
        $products = $this->productModel->getActiveProductTypesWithStock($itemsPerPage, $offset, $searchTerm, $orderBy, $orderDir);
        $totalProducts = $this->productModel->getTotalActiveProductTypesCount($searchTerm);
        $totalPages = ceil($totalProducts / $itemsPerPage);

        $data = [
            'products' => $products,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalProducts' => $totalProducts,
            'searchTerm' => $searchTerm,
            'orderBy' => $orderBy,
            'orderDir' => $orderDir,
            'is_product_list_page' => true // Đánh dấu đây là trang /product/list
        ];

        // Có thể dùng chung view index.php hoặc tạo view product-list.php riêng
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/index.php'; 
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }


    /**
     * Hiển thị chi tiết một sản phẩm.
     * URL: /product/show/{id}
     */
    public function show($id = null) {
        if ($id === null || !is_numeric($id)) {
            display_404_page("ID sản phẩm không hợp lệ.");
            return;
        }

        // findProductById đã được cập nhật để lấy available_stock, gallery_images, và video_url
        $product = $this->productModel->findProductById((int)$id);

        if (!$product) {
            display_404_page("Loại sản phẩm bạn tìm kiếm không tồn tại hoặc không hoạt động.");
            return;
        }

        // Chuẩn bị các biến SEO cho trang chi tiết
        $page_title = htmlspecialchars($product['name']);
        $meta_description = create_excerpt(htmlspecialchars(strip_tags($product['description'] ?? '')), 155);
        $meta_keywords = "mua " . htmlspecialchars($product['name']) . ", chi tiết " . htmlspecialchars($product['name']);
        if (!empty($product['image_url'])) {
            $page_og_image = $product['image_url']; // Ảnh OG là ảnh đại diện chính
        }
        
        $data = [
            'product' => $product
        ];

        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/product-detail.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }
}
?>