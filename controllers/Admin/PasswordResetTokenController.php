<?php
// /controllers/Admin/PasswordResetTokenController.php

class PasswordResetTokenController extends AdminBaseController {
    private $passwordResetModel;
    private $userModel; // Để lấy danh sách user cho bộ lọc

    public function __construct() {
        parent::__construct();
        if (class_exists('PasswordResetModel')) {
            $this->passwordResetModel = new PasswordResetModel();
        } else {
            die('FATAL ERROR: Class PasswordResetModel không tìm thấy.');
        }
        if (class_exists('UserModel')) {
            $this->userModel = new UserModel();
        } else {
            die('FATAL ERROR: Class UserModel không tìm thấy.');
        }
    }

    /**
     * Hiển thị danh sách các token reset mật khẩu.
     * URL: /admin/password-reset-token  hoặc /admin/password-reset-token/index
     */
    public function index() {
        $pageTitle = "Quản Lý Token Reset Mật Khẩu";
        $currentPageTitle = "Danh Sách Token Reset Mật Khẩu";

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 20;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $filters = [];
        if (isset($_GET['user_id_filter']) && $_GET['user_id_filter'] !== '') $filters['user_id'] = (int)$_GET['user_id_filter'];
        if (isset($_GET['is_used_filter']) && $_GET['is_used_filter'] !== '') $filters['is_used'] = (int)$_GET['is_used_filter'];
        if (!empty($_GET['username_search_filter'])) $filters['username_search'] = sanitize_input($_GET['username_search_filter']);
        if (!empty($_GET['selector_search_filter'])) $filters['selector_search'] = sanitize_input($_GET['selector_search_filter']);

        $tokens = $this->passwordResetModel->adminGetAllPasswordResetTokens($itemsPerPage, $offset, $filters);
        $totalTokens = $this->passwordResetModel->adminGetTotalPasswordResetTokensCount($filters);
        $totalPages = ceil($totalTokens / $itemsPerPage);
        
        $allUsers = $this->userModel->getAllUsers(10000, 0); // Lấy nhiều user cho dropdown filter

        $data = [
            'tokens' => $tokens,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalTokens' => $totalTokens,
            'filters' => $filters,
            'allUsers' => $allUsers
        ];

        $this->loadAdminView('manage-password-reset-tokens', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xóa một token reset mật khẩu.
     * URL: /admin/password-reset-token/delete (POST với token_id)
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            set_flash_message('admin_token_error', 'Yêu cầu không hợp lệ.', MSG_ERROR);
            redirect(base_url('admin/password-reset-token'));
            return;
        }
        $tokenId = isset($_POST['token_id']) ? (int)$_POST['token_id'] : 0;

        if ($tokenId <= 0) {
            set_flash_message('admin_token_error', 'ID token không hợp lệ.', MSG_ERROR);
        } else {
            if ($this->passwordResetModel->deleteTokenById($tokenId)) {
                set_flash_message('admin_token_success', 'Token reset mật khẩu (ID: '.$tokenId.') đã được xóa.', MSG_SUCCESS);
            } else {
                set_flash_message('admin_token_error', 'Không thể xóa token (ID: '.$tokenId.').', MSG_ERROR);
            }
        }
        redirect(base_url('admin/password-reset-token'));
    }

    /**
     * Xóa các token đã hết hạn hoặc đã sử dụng.
     * URL: /admin/password-reset-token/cleanup (có thể là GET hoặc POST)
     */
    public function cleanup() {
        // Nên có xác nhận trước khi thực hiện hành động này
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_cleanup'])) {
            $deletedCount = $this->passwordResetModel->deleteExpiredOrUsedTokens();
            if ($deletedCount !== false) {
                set_flash_message('admin_token_success', "Đã xóa {$deletedCount} token hết hạn hoặc đã sử dụng.", MSG_SUCCESS);
            } else {
                set_flash_message('admin_token_error', "Lỗi khi dọn dẹp token.", MSG_ERROR);
            }
        } elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             set_flash_message('admin_token_info', "Bạn có chắc chắn muốn xóa tất cả token đã hết hạn và đã sử dụng không? Hành động này không thể hoàn tác.", MSG_INFO);
             // Có thể hiển thị một form xác nhận nhỏ ở đây hoặc trực tiếp redirect
        }
        redirect(base_url('admin/password-reset-token'));
    }
}
?>