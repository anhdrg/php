<?php
// /controllers/Admin/AdminUserController.php

class AdminUserController extends AdminBaseController {
    private $userModel;
    private $transactionModel;

    public function __construct() {
        parent::__construct();
        if (class_exists('UserModel')) {
            $this->userModel = new UserModel();
        } else {
            write_log("FATAL ERROR: Class UserModel không tìm thấy trong AdminUserController.", "ERROR");
            die('Lỗi hệ thống: UserModel không khả dụng.'); 
        }
        if (class_exists('TransactionModel')) {
            $this->transactionModel = new TransactionModel();
        } else {
            write_log("FATAL ERROR: Class TransactionModel không tìm thấy trong AdminUserController.", "ERROR");
            die('Lỗi hệ thống: TransactionModel không khả dụng.'); 
        }
        if (!function_exists('validatePasswordStrength')) { 
             if(file_exists(PROJECT_ROOT . '/includes/validation.php')){
                require_once PROJECT_ROOT . '/includes/validation.php';
             }
        }
    }

    /**
     * Hiển thị danh sách người dùng.
     * URL: /admin/user  hoặc /admin/user/index
     */
    public function index() {
        $pageTitle = "Quản Lý Người Dùng"; 
        $currentPageTitle = "Danh Sách Người Dùng";

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1; 
        if ($currentPage < 1) $currentPage = 1; 
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 10; 
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        $users = $this->userModel->getAllUsers($itemsPerPage, $offset); 
        $totalUsers = $this->userModel->getTotalUsersCount(); 
        $totalPages = ceil($totalUsers / $itemsPerPage);
        
        $data = [
            'users' => $users, 
            'currentPage' => $currentPage, 
            'totalPages' => $totalPages, 
            'totalUsers' => $totalUsers
        ];
        
        $this->loadAdminView('manage-users', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Hiển thị form sửa thông tin người dùng hoặc xử lý việc cập nhật (POST).
     * URL: /admin/user/edit/{id}
     */
    public function edit($id = null) {
        if ($id === null || !is_numeric($id)) { 
            set_flash_message('admin_user_error', 'ID người dùng không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/user')); 
            return;
        } 
        
        $user = $this->userModel->findUserById((int)$id); 
        if (!$user) { 
            set_flash_message('admin_user_error', 'Không tìm thấy người dùng để chỉnh sửa.', MSG_ERROR); 
            redirect(base_url('admin/user')); 
            return;
        } 

        $pageTitle = "Chỉnh Sửa Người Dùng"; 
        $currentPageTitle = "Chỉnh Sửa Thông Tin: " . htmlspecialchars($user['username']); 
        $errors = [];
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST_DATA = $_POST;

            $updatedData = [
                'username' => sanitize_input($_POST_DATA['username'] ?? $user['username']), 
                'email' => sanitize_input($_POST_DATA['email'] ?? $user['email']), 
                'full_name' => sanitize_input($_POST_DATA['full_name'] ?? ($user['full_name'] ?? '')), 
                'balance' => filter_var($_POST_DATA['balance'] ?? $user['balance'], FILTER_VALIDATE_FLOAT, ['flags' => FILTER_NULL_ON_FAILURE]), 
                'role' => in_array($_POST_DATA['role'] ?? $user['role'], [ROLE_USER, ROLE_ADMIN]) ? $_POST_DATA['role'] : $user['role'],
            ];

            if (isset($_POST_DATA['password']) && !empty($_POST_DATA['password'])) { 
                if (!function_exists('validatePasswordStrength') || !validatePasswordStrength($_POST_DATA['password'])) { 
                    $errors['password'] = "Mật khẩu mới phải dài ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số."; 
                } elseif (isset($_POST_DATA['confirm_password']) && $_POST_DATA['password'] !== $_POST_DATA['confirm_password']) { 
                    $errors['confirm_password'] = "Mật khẩu xác nhận không khớp."; 
                } else { $updatedData['password'] = $_POST_DATA['password']; } 
            }

            if (empty($updatedData['username'])) $errors['username'] = "Tên đăng nhập không được để trống."; 
            if (empty($updatedData['email']) || !validateEmail($updatedData['email'])) $errors['email'] = "Địa chỉ email không hợp lệ."; 
            
            if ($updatedData['balance'] === null && isset($_POST_DATA['balance']) && $_POST_DATA['balance'] !== '' && $_POST_DATA['balance'] !== null ) {
                 $errors['balance'] = "Số dư không hợp lệ (phải là một số).";
            } elseif (isset($updatedData['balance']) && (float)$updatedData['balance'] < 0) {
                $errors['balance'] = "Số dư không được âm.";
            } else if (isset($updatedData['balance']) && $updatedData['balance'] !== null) {
                 $updatedData['balance'] = (float)$updatedData['balance'];
            }

            if ($updatedData['username'] !== $user['username']) { 
                if ($this->userModel->findUserByUsername($updatedData['username'])) { $errors['username'] = "Tên đăng nhập này đã tồn tại."; } 
            } 
            if ($updatedData['email'] !== $user['email']) { 
                if ($this->userModel->findUserByEmail($updatedData['email'])) { $errors['email'] = "Địa chỉ email này đã được sử dụng bởi tài khoản khác."; } 
            }

            if (empty($errors)) { 
                try { 
                    if ($this->userModel->adminUpdateUser((int)$id, $updatedData)) { 
                        set_flash_message('admin_user_success', 'Thông tin người dùng đã được cập nhật thành công!', MSG_SUCCESS); 
                        redirect(base_url('admin/user')); 
                    } else { $errors['form'] = "Đã có lỗi xảy ra khi cập nhật người dùng."; } 
                } catch (Exception $e) { $errors['form'] = $e->getMessage(); } 
            } 
            $user = array_merge($user, $updatedData); 
        }

        $data = [
            'user_to_edit' => $user, 
            'errors' => $errors, 
            'form_action' => base_url('admin/user/edit/' . $id), 
            'form_title' => 'Chỉnh Sửa Người Dùng: ' . htmlspecialchars($user['username'])
        ];
        
        $this->loadAdminView('user-form', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xử lý việc xóa người dùng.
     */
    public function delete() { 
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(base_url('admin/user')); return; } 
        
        $userIdToDelete = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;
        if ($userIdToDelete === null) { set_flash_message('admin_user_error', 'ID người dùng không hợp lệ.', MSG_ERROR); redirect(base_url('admin/user')); return; } 
        if ($userIdToDelete === getCurrentUser('id')) { set_flash_message('admin_user_error', 'Bạn không thể tự xóa tài khoản của mình.', MSG_ERROR); redirect(base_url('admin/user')); return; }
        
        $user = $this->userModel->findUserById($userIdToDelete); 
        if (!$user) { set_flash_message('admin_user_error', 'Không tìm thấy người dùng để xóa.', MSG_ERROR); redirect(base_url('admin/user')); return; }
        
        if ($this->userModel->deleteUser($userIdToDelete)) { 
            set_flash_message('admin_user_success', 'Người dùng "'.htmlspecialchars($user['username']).'" đã được xóa!', MSG_SUCCESS);
        } else { 
            set_flash_message('admin_user_error', 'Không thể xóa người dùng. Có thể do ràng buộc dữ liệu.', MSG_ERROR);
        } 
        redirect(base_url('admin/user'));
    }
    
    /**
     * Xử lý việc admin cập nhật số dư thủ công cho người dùng.
     */
    public function update_balance_manual() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('admin/user'));
        }

        $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT) : 0;
        $operation = isset($_POST['operation']) && in_array($_POST['operation'], ['add', 'subtract']) ? $_POST['operation'] : null;
        $description = isset($_POST['description']) ? sanitize_input(trim($_POST['description'])) : '';
        
        if ($userId <= 0 || $amount === false || $amount <= 0 || empty($operation) || empty($description)) {
            set_flash_message('admin_balance_error', 'Dữ liệu không hợp lệ. Vui lòng điền đầy đủ các trường.', MSG_ERROR);
            redirect(base_url('admin/user'));
            return;
        }
        
        $user = $this->userModel->findUserById($userId);
        if (!$user) {
            set_flash_message('admin_balance_error', 'Không tìm thấy người dùng để cập nhật.', MSG_ERROR);
            redirect(base_url('admin/user'));
            return;
        }

        $isSuccess = $this->userModel->updateUserBalance($userId, $amount, $operation);

        if ($isSuccess) {
            $transactionData = [
                'user_id' => $userId,
                'product_id' => null,
                'recovery_code_id' => null,
                'transaction_type' => ($operation === 'add') ? TRANSACTION_TYPE_DEPOSIT : TRANSACTION_TYPE_PURCHASE,
                'amount' => $amount,
                'payment_method' => PAYMENT_METHOD_ADMIN,
                'payment_gateway_txn_id' => 'ADMIN-' . getCurrentUser('id') . '-' . time(),
                'status' => TRANSACTION_STATUS_COMPLETED,
                'description' => "Admin điều chỉnh: " . $description
            ];
            $this->transactionModel->createTransaction($transactionData);
            set_flash_message('admin_balance_success', 'Cập nhật số dư cho người dùng "'.htmlspecialchars($user['username']).'" thành công!', MSG_SUCCESS);
        } else {
            set_flash_message('admin_balance_error', 'Không thể cập nhật số dư. Có thể do số dư không đủ (khi trừ tiền) hoặc lỗi hệ thống.', MSG_ERROR);
        }
        
        redirect(base_url('admin/user'));
    }
}
?>