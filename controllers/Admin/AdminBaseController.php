<?php
// /controllers/Admin/AdminBaseController.php

class AdminBaseController {
    public function __construct() {
        if (!isLoggedIn()) {
            set_flash_message('auth_error', 'Vui lòng đăng nhập để tiếp tục.', MSG_ERROR);
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? base_url('admin/dashboard');
            redirect(base_url('user/login'));
            exit;
        }
        if (!isAdmin()) {
            set_flash_message('admin_auth_error', 'Bạn không có quyền truy cập khu vực quản trị.', MSG_ERROR);
            redirect(base_url());
            exit;
        }
    }

    protected function loadAdminView(string $viewName, array $data = [], string $pageTitle_param = '', string $currentPageTitle_param = '') {
        $admin_page_title = !empty($pageTitle_param) ? $pageTitle_param . ' | Admin - ' . SITE_NAME : 'Admin Panel | ' . SITE_NAME;
        $admin_current_page_title = !empty($currentPageTitle_param) ? $currentPageTitle_param : 'Trang Quản Trị';
        
        $headerPath = PROJECT_ROOT . '/admin/templates/header.php';
        $viewFilePath = PROJECT_ROOT . '/admin/' . $viewName . '.php';
        $footerPath = PROJECT_ROOT . '/admin/templates/footer.php';

        if (!file_exists($headerPath)) die('Lỗi: Không tìm thấy admin header template.');
        require_once $headerPath;
        
        if (file_exists($viewFilePath)) {
            require_once $viewFilePath;
        } else {
            echo "<div class='container-fluid'><div class='alert alert-danger mt-3'>Lỗi: Không tìm thấy file view: " . htmlspecialchars($viewName) . ".php</div></div>";
            write_log("Admin View not found: " . $viewFilePath, "ERROR");
        }
        
        if (!file_exists($footerPath)) die('Lỗi: Không tìm thấy admin footer template.');
        require_once $footerPath;
    }

    protected function handleImageUpload($fileInputName, $targetSubDir = 'uploads') {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] == UPLOAD_ERR_OK) {
            
            $uploadRoot = 'assets/images/';
            $physicalUploadDir = PROJECT_ROOT . '/' . $uploadRoot . rtrim($targetSubDir, '/') . '/';
            
            if (!is_dir($physicalUploadDir)) { 
                if (!@mkdir($physicalUploadDir, 0775, true) && !is_dir($physicalUploadDir)) { 
                    $_SESSION['upload_error_message'] = "Lỗi server: Không thể tạo thư mục upload tại '{$physicalUploadDir}'.";
                    write_log($_SESSION['upload_error_message'], "ERROR"); 
                    return false; 
                } 
            }

            $fileName = basename($_FILES[$fileInputName]['name']); 
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION)); 
            
            $allowedExtensions = defined('ALLOWED_IMAGE_EXTENSIONS') ? ALLOWED_IMAGE_EXTENSIONS : ['jpg', 'jpeg', 'png', 'gif'];
            $maxFileSize = defined('MAX_UPLOAD_FILE_SIZE') ? MAX_UPLOAD_FILE_SIZE : 2 * 1024 * 1024;

            if (!in_array($fileExtension, $allowedExtensions)) { 
                $_SESSION['upload_error_message'] = "Định dạng file không được phép ('{$fileExtension}').";
                return false; 
            } 
            if ($_FILES[$fileInputName]['size'] > $maxFileSize) { 
                $_SESSION['upload_error_message'] = "Kích thước file quá lớn (tối đa " . ($maxFileSize / 1024 / 1024) . "MB).";
                return false; 
            }

            $uniqueFileName = uniqid('img_', true) . '.' . $fileExtension; 
            $targetFilePathOnServer = $physicalUploadDir . $uniqueFileName; 
            
            $pathRelativeToAssets = rtrim(str_replace('assets/', '', $uploadRoot), '/') . '/' . rtrim($targetSubDir, '/') . '/' . $uniqueFileName;

            if (@move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFilePathOnServer)) { 
                return ltrim($pathRelativeToAssets, '/'); 
            } else { 
                $phpError = error_get_last();
                $_SESSION['upload_error_message'] = "Lỗi di chuyển file upload. " . ($phpError['message'] ?? 'Kiểm tra quyền ghi thư mục.');
                write_log("Upload lỗi: Không thể di chuyển file tới '{$targetFilePathOnServer}'.", "ERROR"); 
                return false; 
            }
        } elseif (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] != UPLOAD_ERR_NO_FILE) { 
            $_SESSION['upload_error_message'] = "Lỗi PHP khi upload file. Mã lỗi: " . $_FILES[$fileInputName]['error'];
            return false; 
        }
        return null;
    }
}
?>