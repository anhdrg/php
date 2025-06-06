<?php
// /models/ProductImageModel.php

class ProductImageModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getImagesByProductId($productId) {
        $sql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC";
        $this->db->query($sql);
        $this->db->bind(':product_id', (int)$productId, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function addImage($productId, $imagePath, $altText = null, $sortOrder = 0) {
        $sql = "INSERT INTO product_images (product_id, image_path, alt_text, sort_order) 
                VALUES (:product_id, :image_path, :alt_text, :sort_order)";
        $this->db->query($sql);
        $this->db->bind(':product_id', (int)$productId, PDO::PARAM_INT);
        $this->db->bind(':image_path', $imagePath);
        $this->db->bind(':alt_text', $altText);
        $this->db->bind(':sort_order', (int)$sortOrder, PDO::PARAM_INT);
        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi thêm ảnh vào bộ sưu tập CSDL: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function getImageById($imageId) {
        $this->db->query("SELECT * FROM product_images WHERE id = :id");
        $this->db->bind(':id', (int)$imageId, PDO::PARAM_INT);
        return $this->db->single();
    }

    public function deleteImage($imageId) {
        $image = $this->getImageById($imageId);
        if (!$image) {
            return false; 
        }
        $this->db->query('DELETE FROM product_images WHERE id = :id');
        $this->db->bind(':id', (int)$imageId, PDO::PARAM_INT);
        try {
            if ($this->db->execute()) {
                $physicalPath = PROJECT_ROOT . '/assets/' . $image['image_path'];
                if (file_exists($physicalPath)) {
                    @unlink($physicalPath);
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi xóa ảnh khỏi bộ sưu tập: " . $e->getMessage() . " | ImageID: {$imageId}", 'ERROR');
            return false;
        }
    }
}
?>