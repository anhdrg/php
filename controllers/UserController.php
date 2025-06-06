<?php
// /controllers/UserController.php

class UserController {
    private $userModel;
    private $passwordResetModel;
    private $transactionModel; 

    public function __construct() {
        $this->userModel = new UserModel();
        $this->passwordResetModel = new PasswordResetModel();
        $this->transactionModel = new TransactionModel(); 
        if (!function_exists('validateRegistrationData')) {
            require_once PROJECT_ROOT . '/includes/validation.php';
        }
    }

    public function register() {
        if (isLoggedIn()) {
            redirect(base_url('user/profile'));
        }
        $page_name_for_seo = 'register';
        $page_title = "Đăng Ký Tài Khoản";

        $data = ['username' => '', 'email' => '', 'full_name' => '', 'password' => '', 'confirm_password' => '', 'errors' => []];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $data['username'] = sanitize_input($_POST['username'] ?? '');
            $data['email'] = sanitize_input($_POST['email'] ?? '');
            $data['full_name'] = sanitize_input($_POST['full_name'] ?? '');
            $data['password'] = $_POST['password'] ?? '';
            $data['confirm_password'] = $_POST['confirm_password'] ?? '';
            $data['errors'] = validateRegistrationData($_POST, $this->userModel);

            if (empty($data['errors'])) {
                $userData = ['username' => $data['username'], 'email' => $data['email'], 'password' => $data['password'], 'full_name' => $data['full_name']];
                try {
                    $userId = $this->userModel->createUser($userData);
                    if ($userId) {
                        set_flash_message('register_success', 'Đăng ký tài khoản thành công! Bạn có thể đăng nhập ngay bây giờ.', MSG_SUCCESS);
                        redirect(base_url('user/login'));
                    } else {
                        $data['errors']['form'] = "Đã có lỗi xảy ra trong quá trình đăng ký. Vui lòng thử lại.";
                    }
                } catch (Exception $e) {
                     $data['errors']['form'] = $e->getMessage();
                }
            }
        }
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/register.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    public function login() {
        if (isLoggedIn()) {
            redirect(base_url('user/profile'));
        }
        $page_name_for_seo = 'login';
        $page_title = "Đăng Nhập";
        $data = ['username_or_email' => '', 'password' => '', 'remember_me' => false, 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $data['username_or_email'] = sanitize_input($_POST['username_or_email'] ?? '');
            $data['password'] = $_POST['password'] ?? '';
            $data['remember_me'] = isset($_POST['remember_me']);
            $data['errors'] = validateLoginData($_POST);

            if (empty($data['errors'])) {
                $loggedInUser = $this->userModel->login($data['username_or_email'], $data['password']);
                if ($loggedInUser) {
                    $pre_login_cart_session_id = $_SESSION['cart_session_id'] ?? null;
                    $this->createUserSession($loggedInUser, $data['remember_me']);
                    if ($pre_login_cart_session_id) {
                        $cartModelForMerge = new CartModel();
                        if ($cartModelForMerge->mergeSessionCartToUserCart($pre_login_cart_session_id, $loggedInUser['id'])) {
                            unset($_SESSION['cart_session_id']);
                        } else {
                            write_log("Gộp giỏ hàng thất bại cho user ID: {$loggedInUser['id']}", "WARNING");
                        }
                    }
                    $finalCartModel = new CartModel(); // Cần CartModel để tính lại số lượng sau khi gộp
                    $_SESSION['cart_item_count'] = $finalCartModel->getTotalItemCount();
                    set_flash_message('global_success', 'Đăng nhập thành công!', MSG_SUCCESS);
                    
                    $redirect_url_after_login = $_SESSION['redirect_after_login'] ?? null;
                    unset($_SESSION['redirect_after_login']);

                    if ($redirect_url_after_login) {
                        redirect($redirect_url_after_login);
                    } elseif ($loggedInUser['role'] === ROLE_ADMIN) {
                        redirect(base_url('admin/dashboard'));
                    } else {
                        redirect(base_url('user/profile'));
                    }
                } else {
                    $data['errors']['form'] = "Tên đăng nhập/email hoặc mật khẩu không đúng.";
                }
            }
        }
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/login.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    private function createUserSession($user, $rememberMe = false) {
        if ($rememberMe) {
            $lifetime = time() + (SESSION_COOKIE_LIFETIME_REMEMBER_ME);
            session_set_cookie_params($lifetime, '/', '', false, true); 
        } else {
            $sessionTimeout = defined('SESSION_TIMEOUT') ? SESSION_TIMEOUT : ini_get('session.gc_maxlifetime');
            session_set_cookie_params($sessionTimeout, '/', '', false, true);
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_data'] = [
            'id' => $user['id'], 'username' => $user['username'], 'email' => $user['email'],
            'full_name' => $user['full_name'], 'balance' => $user['balance'], 'role' => $user['role']
        ];
    }

    public function logout() {
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        if (session_status() == PHP_SESSION_NONE) { session_start(); }
        set_flash_message('global_info', 'Bạn đã đăng xuất thành công.', MSG_INFO);
        redirect(base_url('user/login'));
    }

    public function profile() {
        if (!isLoggedIn()) { 
            $_SESSION['redirect_after_login'] = base_url('user/profile');
            redirect(base_url('user/login')); 
        }
        $userId = getCurrentUser('id');
        $currentUserData = $this->userModel->findUserById($userId);
        if (!$currentUserData) { $this->logout(); return; }
        $_SESSION['user_data'] = $currentUserData;

        $page_name_for_seo = 'user_profile';
        $page_title = "Thông Tin Cá Nhân";
        $data = ['user' => $currentUserData, 'errors' => []];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['update_profile'])) {
                $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
                $updateData = ['full_name' => sanitize_input($_POST['full_name'] ?? ''), 
                               'email' => sanitize_input($_POST['email'] ?? '')];
                $profileErrors = [];
                if ($updateData['email'] !== $data['user']['email']) {
                    if (isEmpty($updateData['email'])) { $profileErrors['email'] = "Email không được để trống.";
                    } elseif (!validateEmail($updateData['email'])) { $profileErrors['email'] = "Địa chỉ email không hợp lệ.";
                    } elseif ($this->userModel->findUserByEmail($updateData['email'])) { $profileErrors['email'] = "Email này đã được sử dụng."; }
                }
                if (isset($_POST['full_name']) && !validateLength($updateData['full_name'], 0, 100)) { $profileErrors['full_name'] = "Họ và tên tối đa 100 ký tự.";}

                if (empty($profileErrors)) {
                    try {
                        if ($this->userModel->updateUserProfile($userId, $updateData)) {
                            $_SESSION['user_data']['full_name'] = $updateData['full_name']; $_SESSION['user_data']['email'] = $updateData['email'];
                            $data['user']['full_name'] = $updateData['full_name']; $data['user']['email'] = $updateData['email'];
                            set_flash_message('profile_success', 'Thông tin cá nhân đã được cập nhật.', MSG_SUCCESS);
                        } else { $profileErrors['form_profile'] = "Không thể cập nhật thông tin. Lỗi không xác định."; }
                    } catch (Exception $e) { $profileErrors['form_profile'] = $e->getMessage(); }
                }
                $data['errors'] = array_merge($data['errors'], $profileErrors);

            } elseif (isset($_POST['change_password'])) {
                $passwordData = ['current_password' => $_POST['current_password'] ?? '', 'new_password' => $_POST['new_password'] ?? '', 'confirm_new_password' => $_POST['confirm_new_password'] ?? ''];
                $passwordErrors = [];
                if (isEmpty($passwordData['current_password'])) { $passwordErrors['current_password'] = "Mật khẩu hiện tại không trống."; }
                if (isEmpty($passwordData['new_password'])) { $passwordErrors['new_password'] = "Mật khẩu mới không trống.";
                } elseif (!validatePasswordStrength($passwordData['new_password'])) { $passwordErrors['new_password'] = "Mật khẩu mới ít nhất 8 ký tự, có chữ hoa, thường, số."; }
                if (isEmpty($passwordData['confirm_new_password'])) { $passwordErrors['confirm_new_password'] = "Xác nhận mật khẩu không trống.";
                } elseif ($passwordData['new_password'] !== $passwordData['confirm_new_password']) { $passwordErrors['confirm_new_password'] = "Mật khẩu xác nhận không khớp."; }

                if (empty($passwordErrors)) {
                    if ($this->userModel->verifyCurrentPassword($userId, $passwordData['current_password'])) {
                        if ($this->userModel->updatePassword($userId, $passwordData['new_password'])) {
                            set_flash_message('password_success', 'Mật khẩu đã được thay đổi.', MSG_SUCCESS);
                        } else { $passwordErrors['form_password'] = "Không thể đổi mật khẩu."; }
                    } else { $passwordErrors['current_password'] = "Mật khẩu hiện tại không đúng."; }
                }
                $data['errors'] = array_merge($data['errors'], $passwordErrors);
            }
            // Load lại data user sau khi có thể đã thay đổi
            if (!empty($data['errors']) || isset($_POST['update_profile']) || isset($_POST['change_password'])) {
                 $reloadedUserData = $this->userModel->findUserById($userId);
                 if ($reloadedUserData) { $data['user'] = $reloadedUserData; $_SESSION['user_data'] = $reloadedUserData; } 
                 else { $this->logout(); return; }
            }
        }
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/profile.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    public function forgot_password() {
        if (isLoggedIn()) { redirect(base_url('user/profile')); }
        $page_name_for_seo = 'forgot_password'; $page_title = "Quên Mật Khẩu"; $data = ['email' => '', 'errors' => []];
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS); $email = sanitize_input(trim($_POST['email'] ?? ''));
            if (empty($email)) { $data['errors']['email'] = "Vui lòng nhập email."; } elseif (!validateEmail($email)) { $data['errors']['email'] = "Email không hợp lệ."; } else {
                $user = $this->userModel->findUserByEmail($email);
                if ($user) { $selector = bin2hex(random_bytes(16)); $validator = bin2hex(random_bytes(32)); $expiresDuration = PASSWORD_RESET_TOKEN_DURATION; 
                    if ($this->passwordResetModel->createSelectorValidatorToken($user['id'], $selector, $validator, $expiresDuration)) { $resetLink = base_url('user/reset-password/' . $selector . '/' . $validator); set_flash_message('forgot_password_info', "Nếu email tồn tại, link đặt lại đã gửi (Test link: <a href='{$resetLink}'>Reset Password</a>).", MSG_INFO); redirect(base_url('user/forgot-password')); exit;
                    } else { $data['errors']['form'] = "Không thể tạo yêu cầu."; }
                } else { set_flash_message('forgot_password_info', "Nếu email tồn tại trong hệ thống, một liên kết đặt lại mật khẩu đã được gửi.", MSG_INFO); redirect(base_url('user/forgot-password')); exit; }
            } $data['email'] = $email;
        }
        require_once PROJECT_ROOT . '/views/templates/header.php'; require_once PROJECT_ROOT . '/views/forgot-password.php'; require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    public function reset_password($selector = null, $validator = null) {
        if (isLoggedIn()) { redirect(base_url('user/profile')); }
        $page_name_for_seo = 'reset_password'; $page_title = "Đặt Lại Mật Khẩu Mới";
        $data = ['selector' => $selector, 'validator' => $validator, 'password' => '', 'confirm_password' => '', 'errors' => [], 'isValidToken' => false];
        if (empty($selector) || empty($validator)) { set_flash_message('global_error', "Link không hợp lệ hoặc hết hạn.", MSG_ERROR);
        } else {
            $resetRecord = $this->passwordResetModel->getResetRecordBySelector($selector);
            if ($resetRecord && password_verify($validator, $resetRecord['hashed_validator'])) { $data['isValidToken'] = true; $userId = $resetRecord['user_id'];
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS); $data['password'] = $_POST['password'] ?? ''; $data['confirm_password'] = $_POST['confirm_password'] ?? '';
                    if (empty($data['password'])) { $data['errors']['password'] = "Mật khẩu mới không trống."; } elseif (!validatePasswordStrength($data['password'])) { $data['errors']['password'] = "Mật khẩu mới ít nhất 8 ký tự, có chữ hoa, thường, số."; } elseif ($data['password'] !== $data['confirm_password']) { $data['errors']['confirm_password'] = "Mật khẩu xác nhận không khớp."; }
                    if (empty($data['errors'])) { if ($this->userModel->updatePassword($userId, $data['password'])) { $this->passwordResetModel->markTokenAsUsedBySelector($selector); set_flash_message('login_success', "Mật khẩu đã đặt lại thành công. Vui lòng đăng nhập.", MSG_SUCCESS); redirect(base_url('user/login')); } else { $data['errors']['form'] = "Không thể đặt lại mật khẩu. Thử lại sau."; } }
                }
            } else { set_flash_message('global_error', "Link không hợp lệ, đã sử dụng hoặc hết hạn.", MSG_ERROR); }
        }
        require_once PROJECT_ROOT . '/views/templates/header.php'; require_once PROJECT_ROOT . '/views/reset-password.php'; require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Hiển thị lịch sử tất cả giao dịch của người dùng (mua và nạp).
     * URL: /user/transaction-history
     */
    public function transaction_history() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = base_url('user/transaction-history');
            redirect(base_url('user/login'));
        }
        $userId = getCurrentUser('id');
        $currentUserData = $this->userModel->findUserById($userId);
        if (!$currentUserData) { $this->logout(); return; }
        $_SESSION['user_data'] = $currentUserData;

        $page_name_for_seo = 'transaction_history';
        $page_title = "Lịch Sử Giao Dịch Chung";
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('DEFAULT_ITEMS_PER_PAGE') ? DEFAULT_ITEMS_PER_PAGE : 10;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $transactions = $this->transactionModel->getUserTransactions($userId, $itemsPerPage, $offset);
        $totalTransactions = $this->transactionModel->getTotalUserTransactionsCount($userId);
        $totalPages = ceil($totalTransactions / $itemsPerPage);

        $data = [
            'transactions' => $transactions,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalTransactions' => $totalTransactions
        ];
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/transaction-history.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Hiển thị lịch sử các đơn hàng đã mua (tài khoản game).
     * URL: /user/order-history
     */
    public function order_history() {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = base_url('user/order-history');
            redirect(base_url('user/login'));
        }
        $userId = getCurrentUser('id');
        $currentUserData = $this->userModel->findUserById($userId);
        if (!$currentUserData) { $this->logout(); return; }
        $_SESSION['user_data'] = $currentUserData;

        $page_name_for_seo = 'order_history'; 
        $page_title = "Đơn Hàng Đã Mua";
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('DEFAULT_ITEMS_PER_PAGE') ? DEFAULT_ITEMS_PER_PAGE : 5;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $orders = $this->transactionModel->getUserPurchaseHistory($userId, $itemsPerPage, $offset);
        $totalOrders = $this->transactionModel->getTotalUserPurchaseHistoryCount($userId);
        $totalPages = ceil($totalOrders / $itemsPerPage);

        $data = [
            'orders' => $orders,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalOrders' => $totalOrders
        ];
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/order-history.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }
}
?>