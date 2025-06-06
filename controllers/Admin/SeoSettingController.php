<?php
// /controllers/Admin/SeoSettingController.php

class SeoSettingController extends AdminBaseController {
    private $seoModel;

    public function __construct() {
        parent::__construct(); // Gọi constructor của AdminBaseController
        
        if (class_exists('SeoModel')) {
            $this->seoModel = new SeoModel();
        } else {
            write_log("FATAL ERROR: Class SeoModel không tìm thấy trong SeoSettingController.", "ERROR");
            die('Lỗi hệ thống: SeoModel không khả dụng.');
        }
    }

    /**
     * Hiển thị danh sách các cài đặt SEO và form để thêm mới.
     * URL: /admin/seo-setting hoặc /admin/seo-setting/index
     * (Trước đây là seo_settings trong AdminController cũ)
     */
    public function index() {
        $pageTitle = "Cài Đặt SEO";
        $currentPageTitle = "Quản Lý Meta Tags cho Trang";

        $seoEntries = $this->seoModel->getAllSeoSettings();
        
        // Dữ liệu cho form thêm mới
        $newSeoData = ['page_name' => '', 'meta_title' => '', 'meta_description' => '', 'meta_keywords' => ''];
        $errors_new = []; // Lỗi riêng cho form thêm mới

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_seo_setting'])) {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $newSeoData['page_name'] = trim(sanitize_input($_POST['page_name'] ?? ''));
            $newSeoData['meta_title'] = sanitize_input($_POST['meta_title'] ?? '');
            $newSeoData['meta_description'] = sanitize_input($_POST['meta_description'] ?? '');
            $newSeoData['meta_keywords'] = sanitize_input($_POST['meta_keywords'] ?? '');

            if (empty($newSeoData['page_name'])) {
                $errors_new['page_name_new'] = "Tên trang (Page Name) không được để trống.";
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newSeoData['page_name'])) {
                $errors_new['page_name_new'] = "Page Name chỉ được chứa chữ cái, số, dấu gạch dưới và gạch nối.";
            }

            if (empty($errors_new)) {
                $addResult = $this->seoModel->addSeoSetting($newSeoData);
                if (is_numeric($addResult) && $addResult > 0) { // Kiểm tra có phải là ID hợp lệ không
                    set_flash_message('admin_seo_success', 'Cài đặt SEO mới đã được thêm thành công!', MSG_SUCCESS);
                    redirect(base_url('admin/seo-setting')); // Redirect về trang danh sách của controller này
                } elseif ($addResult === 'error_duplicate_page_name') {
                    $errors_new['page_name_new'] = "Page Name '" . htmlspecialchars($newSeoData['page_name']) . "' đã tồn tại. Vui lòng chọn tên khác.";
                } else {
                    $errors_new['form_new'] = "Không thể thêm cài đặt SEO mới. Vui lòng thử lại.";
                }
            }
        }

        $data = [
            'seoEntries' => $seoEntries,
            'newSeoData' => $newSeoData, 
            'errors_new' => $errors_new      
        ];

        // View này là /admin/seo-settings.php
        $this->loadAdminView('seo-settings', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Hiển thị form sửa cài đặt SEO hoặc xử lý việc cập nhật (POST).
     * URL: /admin/seo-setting/edit/{id}
     * (Trước đây là edit_seo_setting trong AdminController cũ)
     */
    public function edit($id = null) {
        if ($id === null || !is_numeric($id)) {
            set_flash_message('admin_seo_error', 'ID cài đặt SEO không hợp lệ.', MSG_ERROR);
            redirect(base_url('admin/seo-setting')); // Redirect về danh sách của controller này
            return;
        }

        $seoEntry = $this->seoModel->getSeoSettingById((int)$id);
        if (!$seoEntry) {
            set_flash_message('admin_seo_error', 'Không tìm thấy cài đặt SEO để chỉnh sửa.', MSG_ERROR);
            redirect(base_url('admin/seo-setting'));
            return;
        }

        $pageTitle = "Chỉnh Sửa Cài Đặt SEO";
        $currentPageTitle = "Chỉnh Sửa SEO cho Trang: " . htmlspecialchars($seoEntry['page_name']);
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            $updatedData = [
                // page_name không được sửa ở đây, chỉ sửa các meta tags
                'meta_title' => sanitize_input($_POST['meta_title'] ?? ($seoEntry['meta_title'] ?? '')),
                'meta_description' => sanitize_input($_POST['meta_description'] ?? ($seoEntry['meta_description'] ?? '')),
                'meta_keywords' => sanitize_input($_POST['meta_keywords'] ?? ($seoEntry['meta_keywords'] ?? '')),
            ];
            
            // Không có nhiều validation phức tạp cho các trường meta, thường cho phép rỗng
            // Nhưng bạn có thể thêm nếu muốn (ví dụ: giới hạn độ dài)

            if ($this->seoModel->updateSeoSettingById((int)$id, $updatedData)) {
                set_flash_message('admin_seo_success', 'Cài đặt SEO cho trang "' . htmlspecialchars($seoEntry['page_name']) . '" đã được cập nhật thành công!', MSG_SUCCESS);
                redirect(base_url('admin/seo-setting')); // Redirect về danh sách
            } else {
                $errors['form'] = "Đã có lỗi xảy ra khi cập nhật cài đặt SEO. Vui lòng thử lại.";
            }
            // Cập nhật lại $seoEntry với dữ liệu vừa post để hiển thị trên form nếu có lỗi
            $seoEntry = array_merge($seoEntry, $updatedData); 
        }

        $data = [
            'seoEntry' => $seoEntry,
            'errors' => $errors,
            'form_action' => base_url('admin/seo-setting/edit/' . $id),
            'form_title' => 'Chỉnh Sửa Cài Đặt SEO cho Trang: ' . htmlspecialchars($seoEntry['page_name']),
            // submit_button_text sẽ được đặt trong view seo-form.php
        ];

        // View này là /admin/seo-form.php
        $this->loadAdminView('seo-form', $data, $pageTitle, $currentPageTitle);
    }
    
    /**
     * Xử lý việc xóa một cài đặt SEO.
     * URL: /admin/seo-setting/delete (POST với setting_id)
     * (Trước đây là delete_seo_setting trong AdminController cũ)
     */
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            set_flash_message('admin_seo_error', 'Yêu cầu xóa không hợp lệ.', MSG_ERROR);
            redirect(base_url('admin/seo-setting'));
            return;
        }
        $settingId = isset($_POST['setting_id']) ? (int)$_POST['setting_id'] : 0;

        if ($settingId <= 0) {
            set_flash_message('admin_seo_error', 'ID cài đặt SEO không hợp lệ.', MSG_ERROR);
        } else {
            $setting = $this->seoModel->getSeoSettingById($settingId); // Lấy thông tin để hiển thị trong thông báo
            if ($setting && $this->seoModel->deleteSeoSettingById($settingId)) {
                set_flash_message('admin_seo_success', 'Cài đặt SEO cho trang "'.htmlspecialchars($setting['page_name']).'" đã được xóa thành công.', MSG_SUCCESS);
            } else {
                set_flash_message('admin_seo_error', 'Không thể xóa cài đặt SEO hoặc không tìm thấy bản ghi.', MSG_ERROR);
            }
        }
        redirect(base_url('admin/seo-setting')); // Redirect về danh sách
    }
}
?>