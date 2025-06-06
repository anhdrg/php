<?php
// /models/SeoModel.php

class SeoModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Lấy tất cả các cài đặt SEO.
     * @param string $orderBy Cột để sắp xếp.
     * @param string $orderDir Hướng sắp xếp.
     * @return array Danh sách các cài đặt SEO.
     */
    public function getAllSeoSettings($orderBy = 'page_name', $orderDir = 'ASC') {
        $allowedOrderBy = ['id', 'page_name', 'meta_title', 'updated_at'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'page_name';
        $orderDirSanitized = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM seo_settings ORDER BY {$orderBySanitized} {$orderDirSanitized}";
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    /**
     * Lấy thông tin cài đặt SEO bằng ID của bản ghi.
     * @param int $id ID của bản ghi cài đặt SEO.
     * @return mixed Object cài đặt SEO nếu tìm thấy, false nếu không.
     */
    public function getSeoSettingById($id) {
        $this->db->query("SELECT * FROM seo_settings WHERE id = :id");
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /**
     * Lấy thông tin cài đặt SEO bằng tên trang (page_name).
     * @param string $pageName Tên trang (ví dụ: 'home', 'product_list').
     * @return mixed Object cài đặt SEO nếu tìm thấy, false nếu không.
     */
    public function getSeoSettingsByPageName($pageName) {
        $this->db->query("SELECT meta_title, meta_description, meta_keywords FROM seo_settings WHERE page_name = :page_name");
        $this->db->bind(':page_name', $pageName);
        return $this->db->single();
    }

    /**
     * Thêm một cài đặt SEO mới.
     * @param array $data Dữ liệu gồm: page_name, meta_title, meta_description, meta_keywords.
     * @return string|false ID của bản ghi mới nếu thành công, false nếu thất bại hoặc page_name đã tồn tại.
     */
    public function addSeoSetting($data) {
        $this->db->query("SELECT id FROM seo_settings WHERE page_name = :page_name");
        $this->db->bind(':page_name', $data['page_name']);
        if ($this->db->single()) {
            // Page name đã tồn tại, không thể thêm trùng
            // Có thể ném Exception hoặc trả về một giá trị đặc biệt để controller xử lý
            write_log("Cố gắng thêm page_name SEO đã tồn tại: " . $data['page_name'], "WARNING");
            return 'error_duplicate_page_name'; 
        }

        $sql = "INSERT INTO seo_settings (page_name, meta_title, meta_description, meta_keywords) 
                VALUES (:page_name, :meta_title, :meta_description, :meta_keywords)";
        
        $this->db->query($sql);
        $this->db->bind(':page_name', $data['page_name']);
        $this->db->bind(':meta_title', $data['meta_title'] ?? null);
        $this->db->bind(':meta_description', $data['meta_description'] ?? null);
        $this->db->bind(':meta_keywords', $data['meta_keywords'] ?? null);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi thêm cài đặt SEO: " . $e->getMessage() . " | Data: " . json_encode($data), 'ERROR');
            return false;
        }
    }

    /**
     * Cập nhật một cài đặt SEO bằng ID của bản ghi.
     * @param int $id ID của bản ghi cài đặt SEO.
     * @param array $data Dữ liệu cần cập nhật (meta_title, meta_description, meta_keywords). Page_name không được sửa.
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function updateSeoSettingById($id, $data) {
        // page_name không nên được cập nhật qua hàm này để đảm bảo tính nhất quán.
        // Nếu cần sửa page_name, nên xóa và tạo mới hoặc có cơ chế riêng.
        $sql = "UPDATE seo_settings SET 
                    meta_title = :meta_title, 
                    meta_description = :meta_description, 
                    meta_keywords = :meta_keywords,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $this->db->bind(':meta_title', $data['meta_title'] ?? null);
        $this->db->bind(':meta_description', $data['meta_description'] ?? null);
        $this->db->bind(':meta_keywords', $data['meta_keywords'] ?? null);
        
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi cập nhật cài đặt SEO: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false;
        }
    }
    
    /**
     * Xóa một cài đặt SEO bằng ID.
     * @param int $id
     * @return bool
     */
    public function deleteSeoSettingById($id) {
        $this->db->query("DELETE FROM seo_settings WHERE id = :id");
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi xóa cài đặt SEO: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false;
        }
    }
}
?>