<?php
// /controllers/AdminController.php

class AdminController {

    private $productModel;
    private $userModel;
    private $transactionModel;
    private $recoveryCodeModel; // Model cho kho mã game (bảng recovery_codes)
    private $paymentGatewayModel;
    private $seoModel;
    private $passwordResetModel; 

    public function __construct() {
        if (!isAdmin()) {
            set_flash_message('admin_auth_error', 'Bạn không có quyền truy cập khu vực quản trị.', MSG_ERROR);
            redirect(base_url('user/login'));
            exit;
        }
        // Khởi tạo tất cả các model cần thiết
        if (class_exists('ProductModel')) $this->productModel = new ProductModel(); 
            else die('FATAL ERROR: Class ProductModel không tìm thấy trong AdminController constructor.');
        if (class_exists('UserModel')) $this->userModel = new UserModel(); 
            else die('FATAL ERROR: Class UserModel không tìm thấy.');
        if (class_exists('TransactionModel')) $this->transactionModel = new TransactionModel(); 
            else die('FATAL ERROR: Class TransactionModel không tìm thấy.');
        if (class_exists('RecoveryCodeModel')) $this->recoveryCodeModel = new RecoveryCodeModel(); 
            else die('FATAL ERROR: Class RecoveryCodeModel (cho mã game) không tìm thấy.');
        if (class_exists('PaymentGatewayModel')) $this->paymentGatewayModel = new PaymentGatewayModel(); 
            else die('FATAL ERROR: Class PaymentGatewayModel không tìm thấy.');
        if (class_exists('SeoModel')) $this->seoModel = new SeoModel(); 
            else die('FATAL ERROR: Class SeoModel không tìm thấy.');
        if (class_exists('PasswordResetModel')) $this->passwordResetModel = new PasswordResetModel(); 
            else die('FATAL ERROR: Class PasswordResetModel không tìm thấy.');
    }

    public function index() { 
        $admin_page_title = "Admin Dashboard"; 
        $admin_current_page_title = "Dashboard";
        $data = [
            'totalUsers' => $this->userModel->getTotalUsersCount(), 
            'totalProducts' => $this->productModel->adminGetTotalProductsCount(), // Tổng số loại sản phẩm
            'totalAvailableProducts' => $this->productModel->getTotalAvailableProductsCount(), // Tổng số loại sản phẩm còn hàng (còn mã game)
            'totalTransactions' => $this->transactionModel->adminGetTotalTransactionsCount(),
            'totalRevenue' => $this->transactionModel->adminGetTotalRevenue(), 
            'recentTransactions' => $this->transactionModel->adminGetRecentTransactions(5)
        ];
        $view_path = PROJECT_ROOT . '/admin/dashboard.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; 
        require_once $view_path; 
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    // --- QUẢN LÝ LOẠI SẢN PHẨM ---
    public function manage_products() { 
        $admin_page_title = "Quản Lý Loại Sản Phẩm"; 
        $admin_current_page_title = "Danh Sách Loại Sản Phẩm";
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
        $view_path = PROJECT_ROOT . '/admin/manage-products.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; 
        require_once $view_path; 
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    public function product_add() { 
        $admin_page_title = "Thêm Loại Sản Phẩm Mới"; 
        $admin_current_page_title = "Thêm Loại Sản Phẩm";
        $productData = [
            'name' => '', 
            'description' => '', 
            'price' => '', 
            'image_url' => '', 
            'category_id' => null, 
            'is_active' => true // Mặc định là active khi thêm mới
        ]; 
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $productData['name'] = sanitize_input($_POST['name'] ?? ''); 
            $productData['description'] = sanitize_input($_POST['description'] ?? ''); // Cân nhắc dùng trình soạn thảo cho phép HTML an toàn
            $productData['price'] = filter_var($_POST['price'] ?? 0, FILTER_VALIDATE_FLOAT); 
            $productData['category_id'] = isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
            $productData['is_active'] = isset($_POST['is_active']) ? true : false; // Lấy từ checkbox

            if (empty($productData['name'])) $errors['name'] = "Tên loại sản phẩm không được để trống."; 
            if ($productData['price'] === false || $productData['price'] <= 0) $errors['price'] = "Giá sản phẩm không hợp lệ (phải là số dương)."; 
            
            $uploadedImagePath = $this->handleImageUpload('product_image');
            if ($uploadedImagePath === false && !empty($_FILES['product_image']['name'])) { 
                $errors['product_image'] = "Lỗi khi tải ảnh lên. Định dạng (JPG, PNG, GIF) và kích thước (tối đa 2MB)."; 
            } elseif ($uploadedImagePath) { 
                $productData['image_url'] = $uploadedImagePath; 
            }

            if (empty($errors)) { 
                if ($this->productModel->adminAddProduct($productData)) { 
                    set_flash_message('admin_product_success', 'Loại sản phẩm đã được thêm thành công!', MSG_SUCCESS); 
                    redirect(base_url('admin/manage-products')); 
                } else { 
                    $errors['form'] = "Đã có lỗi xảy ra khi thêm loại sản phẩm. Vui lòng thử lại."; 
                } 
            }
        }
        $data = [
            'product' => $productData,
            'errors' => $errors,
            'form_action' => base_url('admin/product-add'),
            'form_title' => 'Thêm Loại Sản Phẩm Mới',
            'submit_button_text' => 'Thêm Loại Sản Phẩm'
            // 'categories' => $this->categoryModel->getAllCategories() // Nếu có
        ];
        $view_path = PROJECT_ROOT . '/admin/product-form.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; 
        require_once $view_path; 
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    public function product_edit($id = null) { 
        if ($id === null || !is_numeric($id)) { 
            set_flash_message('admin_product_error', 'ID loại sản phẩm không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/manage-products')); 
        } 
        $product = $this->productModel->findProductByIdAdmin((int)$id); 
        if (!$product) { 
            set_flash_message('admin_product_error', 'Không tìm thấy loại sản phẩm để chỉnh sửa.', MSG_ERROR); 
            redirect(base_url('admin/manage-products'));
        }
        $admin_page_title = "Chỉnh Sửa Loại Sản Phẩm"; 
        $admin_current_page_title = "Chỉnh Sửa: " . htmlspecialchars($product['name']); 
        $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $updatedData = [
                'name' => sanitize_input($_POST['name'] ?? $product['name']),
                'description' => sanitize_input($_POST['description'] ?? $product['description']),
                'price' => filter_var($_POST['price'] ?? $product['price'], FILTER_VALIDATE_FLOAT),
                'category_id' => isset($_POST['category_id']) && !empty($_POST['category_id']) ? (int)$_POST['category_id'] : ($product['category_id'] ?? null),
                'is_active' => isset($_POST['is_active']) ? true : false, // Lấy từ checkbox
                'image_url' => $product['image_url'] // Giữ ảnh cũ nếu không có ảnh mới được upload
            ];

            if (empty($updatedData['name'])) $errors['name'] = "Tên loại sản phẩm không được để trống."; 
            if ($updatedData['price'] === false || $updatedData['price'] <= 0) $errors['price'] = "Giá không hợp lệ (phải là số dương)."; 
            
            if (!empty($_FILES['product_image']['name'])) { 
                $newImagePath = $this->handleImageUpload('product_image'); 
                if ($newImagePath === false) { 
                    $errors['product_image'] = "Lỗi khi tải ảnh mới lên."; 
                } elseif($newImagePath) { 
                    // Xóa ảnh cũ nếu có và có ảnh mới được upload thành công
                    if ($product['image_url'] && file_exists(PROJECT_ROOT . '/' . $product['image_url'])) { 
                        @unlink(PROJECT_ROOT . '/' . $product['image_url']); 
                    } 
                    $updatedData['image_url'] = $newImagePath; 
                } 
            }
            if (empty($errors)) { 
                if ($this->productModel->adminUpdateProduct((int)$id, $updatedData)) { 
                    set_flash_message('admin_product_success', 'Loại sản phẩm đã được cập nhật thành công!', MSG_SUCCESS); 
                    redirect(base_url('admin/manage-products')); 
                } else { 
                    $errors['form'] = "Lỗi cập nhật loại sản phẩm."; 
                } 
            } 
            $product = array_merge($product, $updatedData); // Cập nhật lại $product để hiển thị trên form nếu có lỗi
        }
        $data = [
            'product' => $product,
            'errors' => $errors,
            'form_action' => base_url('admin/product-edit/' . $id),
            'form_title' => 'Chỉnh Sửa Loại Sản Phẩm: ' . htmlspecialchars($product['name']),
            'submit_button_text' => 'Cập Nhật Loại Sản Phẩm'
            // 'categories' => $this->categoryModel->getAllCategories() // Nếu có
        ];
        $view_path = PROJECT_ROOT . '/admin/product-form.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; 
        require_once $view_path; 
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    
    public function product_delete($id = null) { 
        $is_post_request = $_SERVER['REQUEST_METHOD'] === 'POST'; 
        $product_id_from_post = isset($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $id_to_delete = null;
        if ($is_post_request && $product_id_from_post !== null) { $id_to_delete = $product_id_from_post; } 
        elseif (!$is_post_request && $id !== null && is_numeric($id)) { $id_to_delete = (int)$id; } 
        else { set_flash_message('admin_product_error', 'Yêu cầu xóa không hợp lệ.', MSG_ERROR); redirect(base_url('admin/manage-products')); return; }
        
        $product = $this->productModel->findProductByIdAdmin($id_to_delete); 
        if (!$product) { set_flash_message('admin_product_error', 'Không tìm thấy loại sản phẩm để xóa.', MSG_ERROR); redirect(base_url('admin/manage-products')); return; }
        try { 
            if ($this->productModel->adminDeleteProduct($id_to_delete)) { 
                if ($product['image_url'] && file_exists(PROJECT_ROOT . '/' . $product['image_url'])) { @unlink(PROJECT_ROOT . '/' . $product['image_url']); } 
                set_flash_message('admin_product_success', 'Loại sản phẩm đã được xóa thành công!', MSG_SUCCESS); 
            } 
        } catch (Exception $e) { set_flash_message('admin_product_error', $e->getMessage(), MSG_ERROR); } 
        redirect(base_url('admin/manage-products'));
    }
    
    private function handleImageUpload($fileInputName) { 
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == UPLOAD_ERR_OK) {
            $uploadDir = PROJECT_ROOT . '/' . PRODUCT_IMAGE_UPLOAD_PATH; 
            if (!is_dir($uploadDir)) { if (!@mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) { write_log("Không thể tạo thư mục upload: " . $uploadDir, "ERROR"); return false; } }
            $fileName = basename($_FILES[$fileInputName]['name']); $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); 
            if (!defined('ALLOWED_IMAGE_EXTENSIONS')) define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
            if (!defined('MAX_UPLOAD_FILE_SIZE')) define('MAX_UPLOAD_FILE_SIZE', 2 * 1024 * 1024); // 2MB
            if (!in_array($fileExtension, ALLOWED_IMAGE_EXTENSIONS)) { write_log("Upload lỗi: Định dạng file không hợp lệ - {$fileExtension}", "WARNING"); return false; } 
            if ($_FILES[$fileInputName]['size'] > MAX_UPLOAD_FILE_SIZE) { write_log("Upload lỗi: Kích thước file quá lớn - " . $_FILES[$fileInputName]['size'], "WARNING"); return false; }
            $uniqueFileName = uniqid('prod_', true) . '.' . $fileExtension; $targetFilePath = $uploadDir . $uniqueFileName; 
            $dbImagePath = rtrim(PRODUCT_IMAGE_UPLOAD_PATH, '/') . '/' . $uniqueFileName; 
            if (@move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFilePath)) { return $dbImagePath; } 
            else { write_log("Upload lỗi: Không thể di chuyển file - " . $targetFilePath . " | Lỗi PHP: " . error_get_last()['message'] ?? ($_FILES[$fileInputName]['error'] ?? 'N/A'), "ERROR"); return false; }
        } elseif (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] != UPLOAD_ERR_NO_FILE) { write_log("Upload lỗi: Mã lỗi PHP - " . $_FILES[$fileInputName]['error'], "ERROR"); return false; }
        return null;
    }

    // --- QUẢN LÝ NGƯỜI DÙNG ---
    public function manage_users() {
        $admin_page_title = "Quản Lý Người Dùng"; $admin_current_page_title = "Danh Sách Người Dùng";
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; if ($currentPage < 1) $currentPage = 1; 
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 10; 
        $offset = ($currentPage - 1) * $itemsPerPage;
        $users = $this->userModel->getAllUsers($itemsPerPage, $offset); $totalUsers = $this->userModel->getTotalUsersCount(); 
        $totalPages = ceil($totalUsers / $itemsPerPage);
        $data = ['users' => $users, 'currentPage' => $currentPage, 'totalPages' => $totalPages, 'totalUsers' => $totalUsers];
        $view_path = PROJECT_ROOT . '/admin/manage-users.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function user_edit($id = null) {
        if ($id === null || !is_numeric($id)) { set_flash_message('admin_user_error', 'ID người dùng không hợp lệ.', MSG_ERROR); redirect(base_url('admin/manage-users')); } 
        $user = $this->userModel->findUserById((int)$id); if (!$user) { set_flash_message('admin_user_error', 'Không tìm thấy người dùng.', MSG_ERROR); redirect(base_url('admin/manage-users')); } 
        unset($user['password']);
        $admin_page_title = "Chỉnh Sửa Người Dùng"; $admin_current_page_title = "Chỉnh Sửa: " . htmlspecialchars($user['username']); $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $updatedData = [
                'username' => sanitize_input($_POST['username'] ?? $user['username']), 
                'email' => sanitize_input($_POST['email'] ?? $user['email']), 
                'full_name' => sanitize_input($_POST['full_name'] ?? $user['full_name']), 
                'balance' => filter_var($_POST['balance'] ?? $user['balance'], FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]), 
                'role' => in_array($_POST['role'] ?? $user['role'], [ROLE_USER, ROLE_ADMIN]) ? $_POST['role'] : $user['role'],
            ];
            if (isset($_POST['password']) && !empty($_POST['password'])) { 
                if (!validatePasswordStrength($_POST['password'])) { $errors['password'] = "Mật khẩu mới không hợp lệ (ít nhất 8 ký tự, có chữ hoa, thường, số)."; } 
                elseif (isset($_POST['confirm_password']) && $_POST['password'] !== $_POST['confirm_password']) { $errors['confirm_password'] = "Mật khẩu xác nhận không khớp."; } 
                else { $updatedData['password'] = $_POST['password']; } 
            }
            if (empty($updatedData['username'])) $errors['username'] = "Tên đăng nhập không được trống."; 
            if (empty($updatedData['email']) || !validateEmail($updatedData['email'])) $errors['email'] = "Email không hợp lệ."; 
            if ($updatedData['balance'] === null && isset($_POST['balance']) && $_POST['balance'] !== '' && $_POST['balance'] !== null ) { $errors['balance'] = "Số dư không hợp lệ (phải là số)."; }
            elseif (isset($updatedData['balance']) && $updatedData['balance'] < 0) { $errors['balance'] = "Số dư không được âm."; }
            if ($updatedData['username'] !== $user['username']) { if ($this->userModel->findUserByUsername($updatedData['username'])) { $errors['username'] = "Tên đăng nhập này đã tồn tại."; } } 
            if ($updatedData['email'] !== $user['email']) { if ($this->userModel->findUserByEmail($updatedData['email'])) { $errors['email'] = "Địa chỉ email này đã được sử dụng."; } }
            if (empty($errors)) { 
                try { 
                    if ($this->userModel->adminUpdateUser((int)$id, $updatedData)) { 
                        set_flash_message('admin_user_success', 'Cập nhật thông tin người dùng thành công!', MSG_SUCCESS); 
                        redirect(base_url('admin/manage-users')); 
                    } else { $errors['form'] = "Lỗi khi cập nhật người dùng."; } 
                } catch (Exception $e) { $errors['form'] = $e->getMessage(); } 
            } 
            $user = array_merge($user, $updatedData); 
            if (isset($updatedData['password']) && !empty($errors)) unset($user['password']);
        }
        $data = ['user_to_edit' => $user, 'errors' => $errors, 'form_action' => base_url('admin/user-edit/' . $id), 'form_title' => 'Chỉnh Sửa Người Dùng: ' . htmlspecialchars($user['username'])];
        $view_path = PROJECT_ROOT . '/admin/user-form.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function user_delete($id = null) { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { set_flash_message('admin_user_error', 'Yêu cầu không hợp lệ.', MSG_ERROR); redirect(base_url('admin/manage-users')); return; } 
        $userIdToDelete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        if ($userIdToDelete === null) { set_flash_message('admin_user_error', 'ID người dùng không cung cấp.', MSG_ERROR); redirect(base_url('admin/manage-users')); return; } 
        if ($userIdToDelete === getCurrentUser('id')) { set_flash_message('admin_user_error', 'Không thể tự xóa tài khoản.', MSG_ERROR); redirect(base_url('admin/manage-users')); return; }
        $user = $this->userModel->findUserById($userIdToDelete); 
        if (!$user) { set_flash_message('admin_user_error', 'Không tìm thấy người dùng.', MSG_ERROR); redirect(base_url('admin/manage-users')); return; }
        if ($this->userModel->deleteUser($userIdToDelete)) { set_flash_message('admin_user_success', 'Người dùng "'.htmlspecialchars($user['username']).'" đã xóa!', MSG_SUCCESS);} 
        else { set_flash_message('admin_user_error', 'Không thể xóa người dùng.', MSG_ERROR);} 
        redirect(base_url('admin/manage-users'));
    }

    // --- QUẢN LÝ GIAO DỊCH ---
    public function manage_transactions() { 
        $admin_page_title = "Quản Lý Giao Dịch"; $admin_current_page_title = "Danh Sách Giao Dịch";
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; if ($currentPage < 1) $currentPage = 1; 
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 15; 
        $offset = ($currentPage - 1) * $itemsPerPage;
        $filters = []; 
        if (!empty($_GET['search_term'])) $filters['search_term'] = sanitize_input($_GET['search_term']); 
        if (!empty($_GET['user_id'])) $filters['user_id'] = (int)$_GET['user_id']; 
        if (!empty($_GET['status'])) $filters['status'] = sanitize_input($_GET['status']); 
        if (!empty($_GET['transaction_type'])) $filters['transaction_type'] = sanitize_input($_GET['transaction_type']); 
        if (!empty($_GET['payment_method'])) $filters['payment_method'] = sanitize_input($_GET['payment_method']); 
        if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize_input($_GET['date_from']); 
        if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize_input($_GET['date_to']);
        $orderBy = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 't.created_at'; 
        $orderDir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';
        $transactions = $this->transactionModel->adminGetAllTransactions($itemsPerPage, $offset, $filters, $orderBy, $orderDir); 
        $totalTransactions = $this->transactionModel->adminGetTotalTransactionsCount($filters); 
        $totalPages = ceil($totalTransactions / $itemsPerPage);
        $data = ['transactions' => $transactions, 'currentPage' => $currentPage, 'totalPages' => $totalPages, 'totalTransactions' => $totalTransactions, 'filters' => $filters, 'orderBy' => $orderBy, 'orderDir' => $orderDir];
        $view_path = PROJECT_ROOT . '/admin/manage-transactions.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function view_transaction($id = null) { 
        if ($id === null || !is_numeric($id)) { set_flash_message('admin_transaction_error', 'ID giao dịch không hợp lệ.', MSG_ERROR); redirect(base_url('admin/manage-transactions')); } 
        $transaction = $this->transactionModel->getTransactionById((int)$id); 
        if (!$transaction) { set_flash_message('admin_transaction_error', 'Không tìm thấy giao dịch.', MSG_ERROR); redirect(base_url('admin/manage-transactions')); }
        if ($transaction['user_id']) { $transaction['user'] = $this->userModel->findUserById($transaction['user_id']); } 
        if ($transaction['product_id']) { $transaction['product_type'] = $this->productModel->findProductByIdAdmin($transaction['product_id']); }
         if ($transaction['recovery_code_id']) { $transaction['game_code_sold'] = $this->recoveryCodeModel->adminGetGameCodeById($transaction['recovery_code_id']); }
        $admin_page_title = "Chi Tiết Giao Dịch"; $admin_current_page_title = "Chi Tiết Giao Dịch #" . $transaction['id']; 
        $data = ['transaction' => $transaction];
        $view_path = PROJECT_ROOT . '/admin/view-transaction.php'; 
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    // --- QUẢN LÝ KHO MÃ GAME (BẢNG `recovery_codes`) ---
    public function manage_recovery_codes() {
        $admin_page_title = "Quản Lý Kho Mã Game";
        $admin_current_page_title = "Kho Mã Game / Tài Khoản";
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 15;
        $offset = ($currentPage - 1) * $itemsPerPage;
        $filters = [];
        if (isset($_GET['product_id_filter']) && $_GET['product_id_filter'] !== '') $filters['product_id'] = (int)$_GET['product_id_filter'];
        if (isset($_GET['status_filter']) && $_GET['status_filter'] !== '') $filters['status'] = sanitize_input($_GET['status_filter']);
        if (!empty($_GET['search_code_value_filter'])) $filters['search_code_value'] = sanitize_input($_GET['search_code_value_filter']); // Key cho model
        if (!empty($_GET['buyer_username_filter'])) $filters['buyer_username_search'] = sanitize_input($_GET['buyer_username_filter']);
        
        $gameCodes = $this->recoveryCodeModel->adminGetAllGameCodes($itemsPerPage, $offset, $filters);
        $totalGameCodes = $this->recoveryCodeModel->adminGetTotalGameCodesCount($filters);
        $totalPages = ceil($totalGameCodes / $itemsPerPage);
        $allProductTypes = $this->productModel->adminGetAllProducts(10000, 0); 
        $data = [
            'gameCodes' => $gameCodes, 'currentPage' => $currentPage, 'totalPages' => $totalPages,
            'totalCodes' => $totalGameCodes, 'filters' => $filters, 'allProductTypes' => $allProductTypes,
            'generated_codes_info' => $_SESSION['generated_codes_info'] ?? null
        ];
        unset($_SESSION['generated_codes_info']);
        $view_path = PROJECT_ROOT . '/admin/manage-game-codes.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php';
        require_once $view_path;
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    public function generate_game_codes() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(base_url('admin/manage-recovery-codes')); }
        $productId = isset($_POST['product_id_generate']) ? (int)$_POST['product_id_generate'] : 0;
        $codeDataInput = $_POST['code_value_input'] ?? ''; 
        $adminNotesInput = sanitize_input($_POST['notes_input'] ?? ''); 
        
        $codesToInsert = [];
        $rawCodes = preg_split('/\r\n|\r|\n/', $codeDataInput);
        foreach ($rawCodes as $singleCodeData) {
            $trimmedCodeData = trim($singleCodeData);
            if (!empty($trimmedCodeData)) { $codesToInsert[] = $trimmedCodeData; }
        }

        if ($productId <= 0) { set_flash_message('admin_recovery_error', 'Vui lòng chọn loại sản phẩm.', MSG_ERROR); redirect(base_url('admin/manage-recovery-codes')); return; }
        if (empty($codesToInsert)) { set_flash_message('admin_recovery_error', 'Vui lòng nhập ít nhất một mã game.', MSG_ERROR); redirect(base_url('admin/manage-recovery-codes?product_id_filter='.$productId)); return; }
        $productType = $this->productModel->findProductByIdAdmin($productId);
        if (!$productType) { set_flash_message('admin_recovery_error', 'Loại sản phẩm không tồn tại.', MSG_ERROR); redirect(base_url('admin/manage-recovery-codes')); return; }

        $successfullyAddedCount = 0; $failedCodesData = [];
        foreach ($codesToInsert as $code_data_item) {
            $dataToSave = [
                'product_id' => $productId,
                'code_value' => $code_data_item,
                'notes' => $adminNotesInput,
                'status' => 'available'
            ];
            if ($this->recoveryCodeModel->addGameCode($dataToSave)) { $successfullyAddedCount++;
            } else { $failedCodesData[] = $code_data_item; }
        }
        
        if ($successfullyAddedCount > 0) {
            $flashMessage = "Đã thêm thành công {$successfullyAddedCount} mã game cho loại \"" . htmlspecialchars($productType['name']) . "\".";
            // Lưu mảng các code vừa tạo vào session để view có thể hiển thị nếu muốn chi tiết
            // $_SESSION['generated_codes_info'] = $codesToInsert; // Hoặc chỉ các code thành công
            if (!empty($failedCodesData)) { 
                $flashMessage .= " Các mã sau không thêm được: " . implode(", ", array_map('htmlspecialchars', $failedCodesData)); 
                set_flash_message('admin_recovery_warning', $flashMessage, MSG_WARNING);
            } else { 
                set_flash_message('admin_recovery_success', $flashMessage, MSG_SUCCESS); 
            }
        } else { 
            set_flash_message('admin_recovery_error', 'Không thể thêm mã game nào. Lỗi với các mã: ' . implode(", ", array_map('htmlspecialchars', $failedCodesData)), MSG_ERROR); 
        }
        redirect(base_url('admin/manage-recovery-codes?product_id_filter='.$productId));
    }

    public function delete_game_code() {
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(base_url('admin/manage-recovery-codes')); }
        $codeId = isset($_POST['code_id']) ? (int)$_POST['code_id'] : 0; $redirectProductId = null;
        if ($codeId <= 0) { set_flash_message('admin_recovery_error', 'ID mã game không hợp lệ.', MSG_ERROR);
        } else {
            $codeInfo = $this->recoveryCodeModel->adminGetGameCodeById($codeId);
            if ($codeInfo) $redirectProductId = $codeInfo['product_id'];
            if ($codeInfo && $codeInfo['status'] === 'sold') { set_flash_message('admin_recovery_error', 'Không thể xóa mã game đã bán (ID: '.$codeId.').', MSG_ERROR);
            } elseif ($this->recoveryCodeModel->adminDeleteGameCode($codeId)) { set_flash_message('admin_recovery_success', 'Mã game (ID: '.$codeId.') đã xóa.', MSG_SUCCESS);
            } else { set_flash_message('admin_recovery_error', 'Không thể xóa mã game (ID: '.$codeId.').', MSG_ERROR); }
        }
        redirect(base_url('admin/manage-recovery-codes' . ($redirectProductId ? '?product_id_filter='.$redirectProductId : '' )));
    }

    public function edit_game_code($code_id = null) {
        if ($code_id === null || !is_numeric($code_id)) { set_flash_message('admin_recovery_error', 'ID mã game không hợp lệ.', MSG_ERROR); redirect(base_url('admin/manage-recovery-codes')); }
        $gameCode = $this->recoveryCodeModel->adminGetGameCodeById((int)$code_id);
        if (!$gameCode) { set_flash_message('admin_recovery_error', 'Không tìm thấy mã game để sửa.', MSG_ERROR); redirect(base_url('admin/manage-recovery-codes')); }
        $admin_page_title = "Chỉnh Sửa Mã Game"; $admin_current_page_title = "Chỉnh Sửa Mã Game #" . $gameCode['id'] . " (Loại: " . htmlspecialchars($gameCode['product_name']) . ")"; $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $updatedData = [
                'product_id' => filter_var($_POST['product_id_edit'] ?? $gameCode['product_id'], FILTER_VALIDATE_INT),
                'code_value' => $_POST['code_value_edit'] ?? $gameCode['code_value'],
                'notes' => sanitize_input($_POST['notes_edit'] ?? $gameCode['notes']),
                'status' => sanitize_input($_POST['status_edit'] ?? $gameCode['status'])
            ];
            if (empty($updatedData['code_value'])) $errors['code_value_edit'] = "Nội dung mã game không được trống.";
            if (!in_array($updatedData['status'], ['available', 'sold', 'reserved', 'disabled'])) $errors['status_edit'] = "Trạng thái không hợp lệ.";
            if (empty($updatedData['product_id'])) $errors['product_id_edit'] = "Vui lòng chọn loại sản phẩm.";
            if (empty($errors)) {
                if ($this->recoveryCodeModel->adminUpdateGameCode((int)$code_id, $updatedData)) {
                    set_flash_message('admin_recovery_success', 'Mã game đã cập nhật!', MSG_SUCCESS);
                    redirect(base_url('admin/manage-recovery-codes?product_id_filter='.$updatedData['product_id']));
                } else { $errors['form'] = "Lỗi khi cập nhật mã game."; }
            }
            $gameCode = array_merge($gameCode, $updatedData);
        }
        $allProductTypes = $this->productModel->adminGetAllProducts(10000, 0);
        $data = ['gameCode' => $gameCode, 'errors' => $errors, 'form_action' => base_url('admin/edit-game-code/' . $code_id), 'form_title' => $admin_current_page_title, 'allProductTypes' => $allProductTypes];
        $view_path = PROJECT_ROOT . '/admin/game-code-form.php'; // View mới cho form sửa mã game
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    // --- QUẢN LÝ TOKEN RESET MẬT KHẨU WEBSITE ---
    public function manage_password_reset_tokens() { 
        $admin_page_title = "Quản Lý Token Reset Mật Khẩu"; $admin_current_page_title = "Token Reset Mật Khẩu Website";
        set_flash_message('admin_info', 'Chức năng này đang được xem xét.', MSG_INFO);
        redirect(base_url('admin/dashboard'));
    }

    // --- QUẢN LÝ CỔNG NẠP TIỀN ---
    public function payment_gateways() {
        $admin_page_title = "Quản Lý Cổng Nạp Tiền"; $admin_current_page_title = "Danh Sách Cổng Nạp";
        $gateways = $this->paymentGatewayModel->getAllGateways(); $data = ['gateways' => $gateways];
        $view_path = PROJECT_ROOT . '/admin/manage-payment-gateways.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function edit_payment_gateway($id = null) {
        if ($id === null || !is_numeric($id)) { set_flash_message('admin_gateway_error', 'ID cổng thanh toán không hợp lệ.', MSG_ERROR); redirect(base_url('admin/payment-gateways'));}
        $gateway = $this->paymentGatewayModel->getGatewayById((int)$id); if (!$gateway) { set_flash_message('admin_gateway_error', 'Không tìm thấy cổng thanh toán.', MSG_ERROR); redirect(base_url('admin/payment-gateways'));}
        $admin_page_title = "Chỉnh Sửa Cổng Nạp Tiền"; $admin_current_page_title = "Chỉnh Sửa: " . htmlspecialchars($gateway['name']); $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Lấy tất cả dữ liệu từ POST, không sanitize API keys, checksum keys
            $postData = $_POST; 
            $updatedData = [
                'client_id' => trim($postData['client_id'] ?? ($gateway['client_id'] ?? '')), 
                'api_key' => trim($postData['api_key'] ?? ($gateway['api_key'] ?? '')), 
                'checksum_key' => trim($postData['checksum_key'] ?? ($gateway['checksum_key'] ?? '')), 
                'merchant_id' => trim($postData['merchant_id'] ?? ($gateway['merchant_id'] ?? '')), 
                'is_active' => isset($postData['is_active']) ? 1 : 0, 
                'description' => sanitize_input($postData['description'] ?? ($gateway['description'] ?? '')), 
                'logo_url' => sanitize_input(trim($postData['logo_url'] ?? ($gateway['logo_url'] ?? ''))),
                'settings' => $postData['settings_json'] ?? json_encode($gateway['settings'] ?? new stdClass())
            ];
            
            $parsedSettings = json_decode($updatedData['settings'], true);
            if (json_last_error() !== JSON_ERROR_NONE && !empty(trim($updatedData['settings']))) { 
                $errors['settings_json'] = "Chuỗi JSON cho Cài đặt thêm không hợp lệ."; 
            } else { 
                $updatedData['settings'] = (is_array($parsedSettings) && !empty($parsedSettings)) ? json_encode($parsedSettings) : null;
            }

            if ($updatedData['is_active']) { 
                if ($gateway['name'] === 'PayOS' && (empty($updatedData['client_id']) || empty($updatedData['api_key']) || empty($updatedData['checksum_key']))) { $errors['keys'] = "Với PayOS, Client ID, API Key, Checksum Key không được trống khi kích hoạt."; } 
                if ($gateway['name'] === 'SEPay' && empty($updatedData['api_key'])) { $errors['keys'] = "Với SEPay, API Key không được trống khi kích hoạt."; } 
            }
            if (empty($errors)) { 
                if ($this->paymentGatewayModel->updateGateway((int)$id, $updatedData)) { 
                    set_flash_message('admin_gateway_success', 'Cập nhật cổng thanh toán thành công!', MSG_SUCCESS); 
                    redirect(base_url('admin/payment-gateways')); 
                } else { $errors['form'] = "Lỗi cập nhật cổng thanh toán."; } 
            } 
            $gateway = array_merge($gateway, $updatedData);
            $gateway['settings'] = json_decode($gateway['settings'] ?? '{}', true); 
        } else { 
            $gateway['settings'] = json_decode($gateway['settings'] ?? '{}', true); // Decode cho lần load đầu
        }
        $data = [ 
            'gateway' => $gateway, 'errors' => $errors, 
            'form_action' => base_url('admin/edit-payment-gateway/' . $id), 
            'form_title' => 'Chỉnh Sửa Cổng Nạp: ' . htmlspecialchars($gateway['name'])
        ];
        $view_path = PROJECT_ROOT . '/admin/payment-gateway-form.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php'; 
        require_once $view_path; 
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }

    // --- CÀI ĐẶT SEO ---
    public function seo_settings() {
        $admin_page_title = "Cài Đặt SEO"; $admin_current_page_title = "Quản Lý Meta Tags";
        $seoEntries = $this->seoModel->getAllSeoSettings();
        $newSeoData = ['page_name' => '', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => '']; $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_seo_setting'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $newSeoData['page_name'] = trim(sanitize_input($_POST['page_name'] ?? '')); 
            $newSeoData['meta_title'] = sanitize_input($_POST['meta_title'] ?? '');
            $newSeoData['meta_description'] = sanitize_input($_POST['meta_description'] ?? ''); 
            $newSeoData['meta_keywords'] = sanitize_input($_POST['meta_keywords'] ?? '');
            if (empty($newSeoData['page_name'])) { $errors['page_name_new'] = "Page Name không được trống."; } 
            elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newSeoData['page_name'])) { $errors['page_name_new'] = "Page Name chỉ chứa chữ cái, số, gạch dưới, gạch nối."; }
            if (empty($errors)) {
                $addResult = $this->seoModel->addSeoSetting($newSeoData);
                if (is_numeric($addResult)) { set_flash_message('admin_seo_success', 'Cài đặt SEO mới đã thêm!', MSG_SUCCESS); redirect(base_url('admin/seo-settings')); } 
                elseif ($addResult === 'error_duplicate_page_name') { $errors['page_name_new'] = "Page Name '" . htmlspecialchars($newSeoData['page_name']) . "' đã tồn tại."; } 
                else { $errors['form_new'] = "Không thể thêm cài đặt SEO."; }
            }
        }
        $data = ['seoEntries' => $seoEntries, 'newSeoData' => $newSeoData, 'errors_new' => $errors ];
        $view_path = PROJECT_ROOT . '/admin/seo-settings.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function edit_seo_setting($id = null) {
        if ($id === null || !is_numeric($id)) { set_flash_message('admin_seo_error', 'ID SEO không hợp lệ.', MSG_ERROR); redirect(base_url('admin/seo-settings')); }
        $seoEntry = $this->seoModel->getSeoSettingById((int)$id);
        if (!$seoEntry) { set_flash_message('admin_seo_error', 'Không tìm thấy cài đặt SEO.', MSG_ERROR); redirect(base_url('admin/seo-settings')); }
        $admin_page_title = "Chỉnh Sửa SEO"; $admin_current_page_title = "Chỉnh Sửa SEO cho: " . htmlspecialchars($seoEntry['page_name']); $errors = [];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $updatedData = [
                'meta_title' => sanitize_input($_POST['meta_title'] ?? $seoEntry['meta_title']),
                'meta_description' => sanitize_input($_POST['meta_description'] ?? $seoEntry['meta_description']),
                'meta_keywords' => sanitize_input($_POST['meta_keywords'] ?? $seoEntry['meta_keywords']),
            ];
            if ($this->seoModel->updateSeoSettingById((int)$id, $updatedData)) {
                set_flash_message('admin_seo_success', 'Cài đặt SEO cho "' . htmlspecialchars($seoEntry['page_name']) . '" đã cập nhật!', MSG_SUCCESS);
                redirect(base_url('admin/seo-settings'));
            } else { $errors['form'] = "Lỗi cập nhật SEO."; }
            $seoEntry = array_merge($seoEntry, $updatedData);
        }
        $data = [ 'seoEntry' => $seoEntry, 'errors' => $errors, 'form_action' => base_url('admin/edit-seo-setting/' . $id), 'form_title' => 'Chỉnh Sửa SEO cho: ' . htmlspecialchars($seoEntry['page_name']) ];
        $view_path = PROJECT_ROOT . '/admin/seo-form.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php'; require_once $view_path; require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
    public function delete_seo_setting() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(base_url('admin/seo-settings')); }
        $settingId = isset($_POST['setting_id']) ? (int)$_POST['setting_id'] : 0;
        if ($settingId <= 0) { set_flash_message('admin_seo_error', 'ID SEO không hợp lệ.', MSG_ERROR);
        } else {
            $setting = $this->seoModel->getSeoSettingById($settingId);
            if ($setting && $this->seoModel->deleteSeoSettingById($settingId)) { set_flash_message('admin_seo_success', 'Cài đặt SEO cho "'.htmlspecialchars($setting['page_name']).'" đã xóa.', MSG_SUCCESS);
            } else { set_flash_message('admin_seo_error', 'Không thể xóa hoặc không tìm thấy cài đặt SEO.', MSG_ERROR); }
        }
        redirect(base_url('admin/seo-settings'));
    }

    // --- THỐNG KÊ ---
    public function statistics() {
        $admin_page_title = "Thống Kê Hệ Thống";
        $admin_current_page_title = "Thống Kê";
        $data = [
            'total_users' => $this->userModel->getTotalUsersCount(),
            'total_products' => $this->productModel->adminGetTotalProductsCount(),
            'total_transactions' => $this->transactionModel->adminGetTotalTransactionsCount(),
            'total_revenue_completed' => $this->transactionModel->adminGetTotalRevenue(),
            'revenue_by_month_labels' => json_encode(['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']),
            'revenue_by_month_data' => json_encode([100,200,150,250,300,220,180,350,400,330,280,450]),
            'user_role_admin_count' => $this->userModel->getCountByRole(ROLE_ADMIN),
            'user_role_user_count' => $this->userModel->getCountByRole(ROLE_USER),
        ];
        $view_path = PROJECT_ROOT . '/admin/statistics.php';
        require_once PROJECT_ROOT . '/admin/templates/header.php';
        require_once $view_path;
        require_once PROJECT_ROOT . '/admin/templates/footer.php';
    }
}
?>