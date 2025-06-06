<?php
// /controllers/Admin/AdminProductController.php

class AdminProductController extends AdminBaseController {
    private $productModel;
    private $productImageModel; 
    // private $categoryModel; // Bỏ comment và khởi tạo nếu bạn dùng quản lý danh mục

    public function __construct() {
        parent::__construct();
        if (class_exists('ProductModel')) {
            $this->productModel = new ProductModel();
        } else { 
            write_log("FATAL ERROR: Class ProductModel không tìm thấy trong AdminProductController.", "ERROR");
            die('Lỗi hệ thống: ProductModel không khả dụng.');
        }
        if (class_exists('ProductImageModel')) { 
            $this->productImageModel = new ProductImageModel();
        } else {
            write_log("FATAL ERROR: Class ProductImageModel không tìm thấy trong AdminProductController.", "ERROR");
            die('Lỗi hệ thống: ProductImageModel không khả dụng.');
        }
    }

    /**
     * Hiển thị danh sách các loại sản phẩm.
     */
    public function index() { 
        $pageTitle = "Quản Lý Loại Sản Phẩm"; 
        $currentPageTitle = "Danh Sách Loại Sản Phẩm";

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
        if ($currentPage < 1) $currentPage = 1; 
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 10; 
        $offset = ($currentPage - 1) * $itemsPerPage;
        $searchTerm = isset($_GET['search']) ? sanitize_input($_GET['search']) : null;
        
        $products = $this->productModel->adminGetAllProducts($itemsPerPage, $offset, $searchTerm); 
        $totalProducts = $this->productModel->adminGetTotalProductsCount($searchTerm); 
        $totalPages = ceil($totalProducts / $itemsPerPage);
        $data = [
            'products' => $products, 
            'currentPage' => $currentPage, 
            'totalPages' => $totalPages, 
            'totalProducts' => $totalProducts, 
            'searchTerm' => $searchTerm
        ];
        
        $this->loadAdminView('manage-products', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Hiển thị form thêm loại sản phẩm mới hoặc xử lý việc thêm (POST).
     */
    public function add() { 
        $pageTitle = "Thêm Loại Sản Phẩm Mới"; 
        $currentPageTitle = "Thêm Loại Sản Phẩm";
        $productData = [
            'name' => '', 'description' => '', 'price' => '', 
            'image_url' => '', 'video_url' => '', 
            'category_id' => null, 'is_active' => true 
        ]; 
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_DATA = $_POST;
            $productData['name'] = sanitize_input($_POST_DATA['name'] ?? ''); 
            $productData['description'] = sanitize_input($_POST_DATA['description'] ?? '');
            $productData['price'] = filter_var($_POST_DATA['price'] ?? 0, FILTER_VALIDATE_FLOAT); 
            $productData['video_url'] = isset($_POST_DATA['video_url']) ? filter_var(trim($_POST_DATA['video_url']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : null;
            $productData['category_id'] = isset($_POST_DATA['category_id']) && !empty($_POST_DATA['category_id']) ? (int)$_POST_DATA['category_id'] : null;
            $productData['is_active'] = isset($_POST_DATA['is_active']); 

            if (empty($productData['name'])) $errors['name'] = "Tên loại sản phẩm không được để trống."; 
            if ($productData['price'] === false || $productData['price'] <= 0) $errors['price'] = "Giá sản phẩm không hợp lệ."; 
            if (isset($_POST_DATA['video_url']) && !empty(trim($_POST_DATA['video_url'])) && $productData['video_url'] === null) {
                 $errors['video_url'] = "Link video không hợp lệ.";
            }
            
            $uploadedImagePath = $this->handleImageUpload('product_image', 'products/main');
            if ($uploadedImagePath === false && !empty($_FILES['product_image']['name'])) { 
                $errors['product_image'] = "Lỗi tải ảnh đại diện: " . ($_SESSION['upload_error_message'] ?? "Lỗi không xác định.");
                unset($_SESSION['upload_error_message']);
            } elseif ($uploadedImagePath !== null) { 
                $productData['image_url'] = $uploadedImagePath; 
            }

            if (empty($errors)) { 
                $newProductId = $this->productModel->adminAddProduct($productData);
                if ($newProductId) {
                    if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
                        $galleryFiles = $_FILES['gallery_images'];
                        $sortOrder = 0;
                        foreach ($galleryFiles['name'] as $key => $name) {
                            if ($galleryFiles['error'][$key] == UPLOAD_ERR_OK && !empty($name)) {
                                $_FILES['temp_gallery_image'] = ['name' => $name, 'type' => $galleryFiles['type'][$key], 'tmp_name' => $galleryFiles['tmp_name'][$key], 'error' => $galleryFiles['error'][$key], 'size' => $galleryFiles['size'][$key]];
                                $galleryImagePath = $this->handleImageUpload('temp_gallery_image', 'products/gallery');
                                unset($_FILES['temp_gallery_image']); 
                                if ($galleryImagePath) {
                                    $altText = pathinfo($name, PATHINFO_FILENAME);
                                    if(!$this->productImageModel->addImage($newProductId, $galleryImagePath, $altText, $sortOrder++)){
                                        write_log("Lỗi ProductImageModel::addImage cho gallery image '{$name}' SP ID {$newProductId}.", "ERROR");
                                    }
                                }
                            }
                        }
                    }
                    set_flash_message('admin_product_success', 'Loại sản phẩm đã được thêm thành công!', MSG_SUCCESS); 
                    redirect(base_url('admin/product')); 
                } else { $errors['form'] = "Lỗi khi thêm loại sản phẩm vào CSDL."; } 
            }
        }
        $data = [
            'product' => $productData,
            'errors' => $errors,
            'form_action' => base_url('admin/product/add'),
            'form_title' => 'Thêm Loại Sản Phẩm Mới',
            'submit_button_text' => 'Thêm Loại Sản Phẩm'
        ];
        $this->loadAdminView('product-form', $data, $pageTitle, $currentPageTitle);
    }
    
    /**
     * Hiển thị form sửa loại sản phẩm hoặc xử lý việc sửa (POST).
     */
    public function edit($id = null) { 
        if ($id === null || !is_numeric($id)) { 
            set_flash_message('admin_product_error', 'ID loại sản phẩm không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/product')); return;
        } 
        $product = $this->productModel->findProductByIdAdmin((int)$id); 
        if (!$product) { 
            set_flash_message('admin_product_error', 'Không tìm thấy loại sản phẩm để chỉnh sửa.', MSG_ERROR); 
            redirect(base_url('admin/product')); return;
        }
        
        $pageTitle = "Chỉnh Sửa Loại Sản Phẩm"; 
        $currentPageTitle = "Chỉnh Sửa: " . htmlspecialchars($product['name']); 
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_DATA = $_POST;
            $updatedData = [
                'name' => sanitize_input($_POST_DATA['name'] ?? $product['name']),
                'description' => sanitize_input($_POST_DATA['description'] ?? $product['description']),
                'price' => filter_var($_POST_DATA['price'] ?? $product['price'], FILTER_VALIDATE_FLOAT),
                'video_url' => isset($_POST_DATA['video_url']) ? filter_var(trim($_POST_DATA['video_url']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : $product['video_url'],
                'category_id' => isset($_POST_DATA['category_id']) && !empty($_POST_DATA['category_id']) ? (int)$_POST_DATA['category_id'] : ($product['category_id'] ?? null),
                'is_active' => isset($_POST_DATA['is_active']),
                'image_url' => $product['image_url'] 
            ];
            
            if (empty($updatedData['name'])) $errors['name'] = "Tên loại sản phẩm không được để trống."; 
            if ($updatedData['price'] === false || $updatedData['price'] <= 0) $errors['price'] = "Giá không hợp lệ (phải là số dương)."; 
            if (isset($_POST_DATA['video_url']) && !empty(trim($_POST_DATA['video_url'])) && $updatedData['video_url'] === null) {
                 $errors['video_url'] = "Link video không hợp lệ.";
            }
            
            if (!empty($_FILES['product_image']['name'])) { 
                $newImagePath = $this->handleImageUpload('product_image', 'products/main'); 
                if ($newImagePath === false) { 
                    $errors['product_image'] = "Lỗi khi tải ảnh đại diện mới: " . ($_SESSION['upload_error_message'] ?? "");
                    unset($_SESSION['upload_error_message']);
                } elseif($newImagePath !== null) { 
                    if ($product['image_url'] && file_exists(PROJECT_ROOT . '/assets/' . $product['image_url'])) { 
                        @unlink(PROJECT_ROOT . '/assets/' . $product['image_url']); 
                    } 
                    $updatedData['image_url'] = $newImagePath; 
                } 
            }
            
            if (empty($errors)) { 
                if ($this->productModel->adminUpdateProduct((int)$id, $updatedData)) {
                    // Xử lý upload ảnh MỚI cho bộ sưu tập
                    if (isset($_FILES['gallery_images']) && is_array($_FILES['gallery_images']['name'])) {
                        $galleryFiles = $_FILES['gallery_images'];
                        $existingGalleryImages = $this->productImageModel->getImagesByProductId((int)$id);
                        $sortOrder = count($existingGalleryImages); 
                        foreach ($galleryFiles['name'] as $key => $name) {
                            if ($galleryFiles['error'][$key] == UPLOAD_ERR_OK && !empty($name)) {
                                $_FILES['temp_gallery_image'] = ['name' => $name, 'type' => $galleryFiles['type'][$key], 'tmp_name' => $galleryFiles['tmp_name'][$key], 'error' => $galleryFiles['error'][$key], 'size' => $galleryFiles['size'][$key]];
                                $galleryImagePath = $this->handleImageUpload('temp_gallery_image', 'products/gallery');
                                unset($_FILES['temp_gallery_image']);
                                if ($galleryImagePath) {
                                    $altText = pathinfo($name, PATHINFO_FILENAME);
                                    if(!$this->productImageModel->addImage((int)$id, $galleryImagePath, $altText, $sortOrder++)){
                                        write_log("Lỗi addImage gallery '{$name}' khi sửa SP ID {$id}.", "ERROR");
                                    }
                                } else { 
                                    write_log("Lỗi handleImageUpload gallery '{$name}' khi sửa SP ID {$id}. " . ($_SESSION['upload_error_message'] ?? ''), "WARNING"); 
                                    unset($_SESSION['upload_error_message']);
                                }
                            }
                        }
                    }
                    set_flash_message('admin_product_success', 'Loại sản phẩm đã được cập nhật!', MSG_SUCCESS); 
                    redirect(base_url('admin/product/edit/' . $id)); 
                } else { $errors['form'] = "Lỗi cập nhật loại sản phẩm."; } 
            } 
            $product = $this->productModel->findProductByIdAdmin((int)$id);
        }
        
        $data = [
            'product' => $product,
            'errors' => $errors,
            'form_action' => base_url('admin/product/edit/' . $id),
            'form_title' => 'Chỉnh Sửa Loại Sản Phẩm: ' . htmlspecialchars($product['name'] ?? ''),
            'submit_button_text' => 'Cập Nhật'
        ];
        $this->loadAdminView('product-form', $data, $pageTitle, $currentPageTitle);
    }
    
    /**
     * Xử lý việc xóa loại sản phẩm.
     */
    public function delete() { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('admin/product')); return;
        }
        $id_to_delete = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        if ($id_to_delete === null) { 
            set_flash_message('admin_product_error', 'ID loại sản phẩm không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/product')); return; 
        }
        try { 
            // ProductModel::adminDeleteProduct đã bao gồm xóa ảnh và kiểm tra ràng buộc
            if ($this->productModel->adminDeleteProduct($id_to_delete)) { 
                set_flash_message('admin_product_success', 'Loại sản phẩm và các dữ liệu liên quan đã được xóa!', MSG_SUCCESS); 
            } 
        } catch (Exception $e) { 
            set_flash_message('admin_product_error', "Lỗi khi xóa: " . $e->getMessage(), MSG_ERROR); 
        } 
        redirect(base_url('admin/product'));
    }
    
    /**
     * Xóa một ảnh gallery cụ thể.
     */
    public function delete_gallery_image() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('admin/product')); return;
        }
        $productImageId = isset($_POST['product_image_id']) ? (int)$_POST['product_image_id'] : 0;
        $productIdRedirect = isset($_POST['product_id_redirect']) ? (int)$_POST['product_id_redirect'] : 0;
        
        if ($productImageId > 0 && $this->productImageModel) {
            // ProductImageModel::deleteImage xóa cả record CSDL và file vật lý
            if ($this->productImageModel->deleteImage($productImageId)) {
                set_flash_message('admin_product_success', 'Đã xóa ảnh khỏi bộ sưu tập.', MSG_SUCCESS);
            } else {
                set_flash_message('admin_product_error', 'Không thể xóa ảnh. Vui lòng thử lại.', MSG_ERROR);
            }
        } else {
            set_flash_message('admin_product_error', 'Thông tin ảnh không hợp lệ.', MSG_ERROR);
        }

        if ($productIdRedirect > 0) {
            redirect(base_url('admin/product/edit/' . $productIdRedirect));
        } else {
            redirect(base_url('admin/product'));
        }
    }
}
?>