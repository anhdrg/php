<?php
// /models/ProductModel.php

class ProductModel {
    private $db;
    private $recoveryCodeModel; 
    private $productImageModel; 

    public function __construct() {
        $this->db = new Database();
        
        // Khởi tạo các model phụ trợ một cách an toàn
        if (class_exists('RecoveryCodeModel')) $this->recoveryCodeModel = new RecoveryCodeModel();
            else write_log("WARNING: Class RecoveryCodeModel không tìm thấy trong ProductModel.", "WARNING");
        if (class_exists('ProductImageModel')) $this->productImageModel = new ProductImageModel();
            else write_log("WARNING: Class ProductImageModel không tìm thấy trong ProductModel.", "WARNING");
    }

    /**
     * Lấy tất cả các loại sản phẩm đang hoạt động.
     * Bao gồm số lượng mã game có sẵn (available_stock).
     */
    public function getActiveProductTypesWithStock($limit = DEFAULT_ITEMS_PER_PAGE, $offset = 0, $searchTerm = null, $orderBy = 'p.created_at', $orderDir = 'DESC') {
        $sql = "SELECT p.*,
                       COUNT(CASE WHEN rc.status = 'available' THEN rc.id ELSE NULL END) as available_stock
                FROM products p
                LEFT JOIN recovery_codes rc ON p.id = rc.product_id 
                WHERE p.is_active = TRUE"; 
        
        $params = [];
        if ($searchTerm) {
            $sql .= " AND (p.name LIKE :searchTerm OR p.description LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }

        $sql .= " GROUP BY p.id, p.name, p.description, p.price, p.image_url, p.video_url, p.is_active, p.category_id, p.created_at, p.updated_at";
        
        $allowedOrderBy = ['p.created_at', 'p.price', 'p.name', 'available_stock'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'p.created_at';
        $orderDirSanitized = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$orderBySanitized} {$orderDirSanitized}, p.id {$orderDirSanitized}";

        $sql .= " LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT); 
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số loại sản phẩm đang hoạt động.
     */
    public function getTotalActiveProductTypesCount($searchTerm = null) {
        $sql = "SELECT COUNT(p.id) as total FROM products p WHERE p.is_active = TRUE";
        $params = [];
        if ($searchTerm) {
            $sql .= " AND (p.name LIKE :searchTerm OR p.description LIKE :searchTerm)";
            $params[':searchTerm'] = '%' . $searchTerm . '%';
        }
        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Lấy thông tin chi tiết của một loại sản phẩm bằng ID (nếu nó active).
     */
    public function findProductById($id) {
        $this->db->query('SELECT * FROM products WHERE id = :id AND is_active = TRUE');
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $product = $this->db->single();

        if ($product) {
            if ($this->recoveryCodeModel) {
                $product['available_stock'] = $this->recoveryCodeModel->countAvailableCodesForProduct((int)$id);
            } else { $product['available_stock'] = 0; }
            
            if ($this->productImageModel) {
                $product['gallery_images'] = $this->productImageModel->getImagesByProductId((int)$id);
            } else { $product['gallery_images'] = []; }
        }
        return $product ? $product : false;
    }

    /**
     * (Admin) Lấy thông tin chi tiết của một loại sản phẩm (không quan tâm active).
     */
    public function findProductByIdAdmin($id) {
        $this->db->query('SELECT * FROM products WHERE id = :id');
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $product = $this->db->single();

        if ($product) {
            if ($this->recoveryCodeModel) {
                $product['available_stock'] = $this->recoveryCodeModel->countAvailableCodesForProduct((int)$id);
                $product['total_codes_linked'] = $this->recoveryCodeModel->adminGetTotalGameCodesCount(['product_id' => (int)$id]);
            } else { $product['available_stock'] = 0; $product['total_codes_linked'] = 0; }
            
            if ($this->productImageModel) {
                $product['gallery_images'] = $this->productImageModel->getImagesByProductId((int)$id);
            } else { $product['gallery_images'] = []; }
        }
        return $product ? $product : false;
    }

    // --- Các hàm cho Admin Panel ---
    public function adminGetAllProducts($limit = ADMIN_DEFAULT_ITEMS_PER_PAGE, $offset = 0, $searchTerm = null, $orderBy = 'p.id', $orderDir = 'DESC') {
        $sql = "SELECT p.*, 
                       (SELECT COUNT(rc.id) FROM recovery_codes rc WHERE rc.product_id = p.id AND rc.status = 'available') as available_stock,
                       (SELECT COUNT(rc_total.id) FROM recovery_codes rc_total WHERE rc_total.product_id = p.id) as total_codes_linked
                FROM products p";
        $params = [];
        if ($searchTerm) { $sql .= " WHERE (p.name LIKE :searchTerm OR p.description LIKE :searchTerm OR p.id LIKE :searchTerm)"; $params[':searchTerm'] = '%' . $searchTerm . '%'; }
        $allowedOrderBy = ['p.id', 'p.name', 'p.price', 'p.is_active', 'p.created_at', 'p.updated_at', 'available_stock', 'total_codes_linked'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'p.id';
        $orderDirSanitized = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$orderBySanitized} {$orderDirSanitized}, p.id {$orderDirSanitized}";
        $sql .= " LIMIT :limit OFFSET :offset";
        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT); 
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    public function adminGetTotalProductsCount($searchTerm = null) {
        $sql = "SELECT COUNT(id) as total FROM products";
        $params = [];
        if ($searchTerm) { $sql .= " WHERE (name LIKE :searchTerm OR description LIKE :searchTerm OR id LIKE :searchTerm)"; $params[':searchTerm'] = '%' . $searchTerm . '%'; }
        $this->db->query($sql);
         foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    public function adminAddProduct($data) {
        $sql = "INSERT INTO products (name, description, price, image_url, video_url, category_id, is_active) 
                VALUES (:name, :description, :price, :image_url, :video_url, :category_id, :is_active)";
        $this->db->query($sql);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':price', (float)($data['price'] ?? 0));
        $this->db->bind(':image_url', $data['image_url'] ?? null); // Lưu trực tiếp đường dẫn ảnh
        $this->db->bind(':video_url', $data['video_url'] ?? null);
        $this->db->bind(':category_id', $data['category_id'] ?? null, PDO::PARAM_INT);
        $this->db->bind(':is_active', (bool)($data['is_active'] ?? true), PDO::PARAM_BOOL);
        try {
            if ($this->db->execute()) { return $this->db->lastInsertId(); }
            return false;
        } catch (PDOException $e) { 
            write_log("Lỗi admin thêm loại sản phẩm: " . $e->getMessage(), 'ERROR');
            return false; 
        }
    }

    public function adminUpdateProduct($id, $data) {
        $sql = "UPDATE products SET 
                    name = :name, 
                    description = :description, 
                    price = :price, 
                    image_url = :image_url, 
                    video_url = :video_url,
                    category_id = :category_id,
                    is_active = :is_active,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        $this->db->query($sql);
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $this->db->bind(':name', $data['name']);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':price', (float)($data['price'] ?? 0));
        $this->db->bind(':image_url', $data['image_url'] ?? null); // Cập nhật trực tiếp đường dẫn ảnh
        $this->db->bind(':video_url', $data['video_url'] ?? null);
        $this->db->bind(':category_id', $data['category_id'] ?? null, PDO::PARAM_INT);
        $this->db->bind(':is_active', (bool)($data['is_active'] ?? true), PDO::PARAM_BOOL);
        try {
            return $this->db->execute();
        } catch (PDOException $e) { 
            write_log("Lỗi admin cập nhật loại sản phẩm: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false; 
        }
    }

    public function adminDeleteProduct($id) {
        // Lấy thông tin sản phẩm trước khi xóa để có thể xóa file ảnh
        $product = $this->findProductByIdAdmin((int)$id);
        if (!$product) {
            throw new Exception("Không tìm thấy loại sản phẩm để xóa.");
        }

        // Kiểm tra mã game liên quan
        if ($this->recoveryCodeModel) {
            $linkedCodesCount = $this->recoveryCodeModel->adminGetTotalGameCodesCount(['product_id' => (int)$id]);
            if ($linkedCodesCount > 0) {
                throw new Exception("Không thể xóa loại sản phẩm này vì còn {$linkedCodesCount} mã game trong kho.");
            }
        } else {
            throw new Exception("Lỗi hệ thống: Không thể kiểm tra mã game liên quan.");
        }

        // Xóa các liên kết ảnh gallery trước
        if ($this->productImageModel) {
            $galleryImages = $this->productImageModel->getImagesByProductId((int)$id);
            if ($galleryImages) {
                foreach ($galleryImages as $image) {
                    $this->productImageModel->deleteImage($image['id']); // Hàm này sẽ xóa record và file
                }
            }
        }

        // Xóa record sản phẩm khỏi DB
        $this->db->query('DELETE FROM products WHERE id = :id');
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        try {
            if ($this->db->execute()) {
                // Nếu xóa record thành công, xóa file ảnh đại diện chính
                if (!empty($product['image_url']) && file_exists(PROJECT_ROOT . '/assets/' . $product['image_url'])) {
                    @unlink(PROJECT_ROOT . '/assets/' . $product['image_url']);
                }
                return true;
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi admin xóa loại sản phẩm: " . $e->getMessage(), 'ERROR');
            if ($e->getCode() == '23000') { 
                 throw new Exception("Không thể xóa loại sản phẩm này do có ràng buộc khóa ngoại (ví dụ: giao dịch cũ).");
            }
            throw $e;
        }
    }
}
?>