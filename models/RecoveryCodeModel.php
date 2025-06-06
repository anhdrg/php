<?php
// /models/RecoveryCodeModel.php

class RecoveryCodeModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * (Admin) Thêm một mã game mới vào kho.
     * @param array $data Gồm: product_id, code_value, notes (optional), status (optional, default 'available').
     * @return string|false ID của mã game mới nếu thành công, false nếu thất bại.
     */
    public function addGameCode(array $data) {
        $sql = "INSERT INTO recovery_codes (product_id, code_value, notes, status) 
                VALUES (:product_id, :code_value, :notes, :status)";
        
        $this->db->query($sql);
        $this->db->bind(':product_id', $data['product_id'], PDO::PARAM_INT);
        $this->db->bind(':code_value', $data['code_value']); 
        $this->db->bind(':notes', $data['notes'] ?? null);      
        $this->db->bind(':status', $data['status'] ?? 'available');

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi thêm mã game vào kho: " . $e->getMessage() . " | Data: " . json_encode($data), 'ERROR');
            return false;
        }
    }

    /**
     * Lấy một mã game có sẵn cho một loại sản phẩm và tùy chọn đánh dấu là 'reserved'.
     * @param int $productId ID của loại sản phẩm.
     * @param bool $markAsReserved Nếu true, sẽ cố gắng cập nhật status thành 'reserved'.
     * @param int|null $userIdToReserve (Tùy chọn) ID người dùng để gán tạm thời nếu đang reserve.
     * @return array|false Dữ liệu của mã game nếu tìm thấy, false nếu không có sẵn hoặc lỗi.
     */
    public function getAndReserveAvailableCode($productId, $markAsReserved = false, $userIdToReserve = null) {
        $this->db->beginTransaction();
        try {
            $this->db->query("SELECT * FROM recovery_codes 
                              WHERE product_id = :product_id AND status = 'available' 
                              ORDER BY created_at ASC LIMIT 1 FOR UPDATE");
            $this->db->bind(':product_id', $productId, PDO::PARAM_INT);
            $availableCode = $this->db->single();

            if ($availableCode) {
                if ($markAsReserved) {
                    $this->db->query("UPDATE recovery_codes SET status = 'reserved', user_id_buyer = :user_id_buyer, updated_at = CURRENT_TIMESTAMP 
                                      WHERE id = :id AND status = 'available'");
                    $this->db->bind(':user_id_buyer', $userIdToReserve, PDO::PARAM_INT);
                    $this->db->bind(':id', $availableCode['id'], PDO::PARAM_INT);
                    if (!$this->db->execute() || $this->db->rowCount() == 0) {
                        $this->db->rollBack();
                        write_log("Không thể reserve mã game ID: {$availableCode['id']} cho ProductID: {$productId}", "WARNING");
                        return false; 
                    }
                    $availableCode['status'] = 'reserved';
                    $availableCode['user_id_buyer'] = $userIdToReserve;
                }
                $this->db->commit();
                return $availableCode;
            } else {
                $this->db->rollBack();
                return false; 
            }
        } catch (PDOException $e) {
            $this->db->rollBack();
            write_log("Lỗi khi lấy và reserve mã game: " . $e->getMessage(), "ERROR");
            return false;
        }
    }

    /**
     * Đánh dấu một mã game là đã bán.
     * @param int $recoveryCodeId ID của mã game trong bảng recovery_codes.
     * @param int $buyerUserId ID của người mua.
     * @param int $transactionId ID của giao dịch.
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function markCodeAsSold($recoveryCodeId, $buyerUserId, $transactionId) {
        $sql = "UPDATE recovery_codes SET 
                    status = 'sold', 
                    user_id_buyer = :user_id_buyer, 
                    transaction_id = :transaction_id, 
                    sold_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id AND (status = 'available' OR (status = 'reserved' AND user_id_buyer = :user_id_buyer_check))"; 
                // Chỉ cập nhật nếu đang available hoặc reserved cho đúng người dùng này
    
        $this->db->query($sql);
        $this->db->bind(':user_id_buyer', $buyerUserId, PDO::PARAM_INT);
        $this->db->bind(':transaction_id', $transactionId, PDO::PARAM_INT);
        $this->db->bind(':id', $recoveryCodeId, PDO::PARAM_INT);
        $this->db->bind(':user_id_buyer_check', $buyerUserId, PDO::PARAM_INT); // Dùng cho điều kiện check reserved

        try {
            if ($this->db->execute()) {
                return $this->db->rowCount() > 0; 
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi đánh dấu mã game đã bán: " . $e->getMessage() . " | ID: {$recoveryCodeId}", 'ERROR');
            return false;
        }
    }
    
    /**
     * Hoàn lại trạng thái của một mã game từ 'reserved' về 'available'.
     * @param int $recoveryCodeId
     * @return bool
     */
    public function unreserveCode($recoveryCodeId) {
        $sql = "UPDATE recovery_codes SET status = 'available', user_id_buyer = NULL, transaction_id = NULL, sold_at = NULL, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id AND status = 'reserved'";
        $this->db->query($sql);
        $this->db->bind(':id', $recoveryCodeId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi hoàn lại trạng thái mã game: " . $e->getMessage() . " | ID: {$recoveryCodeId}", 'ERROR');
            return false;
        }
    }

    /**
     * Lấy chi tiết một mã game đã bán cho một người dùng cụ thể (để hiển thị cho người dùng).
     * @param int $recoveryCodeId ID của mã game.
     * @param int $userId ID của người dùng (để xác thực quyền xem).
     * @return array|false Dữ liệu mã game nếu hợp lệ, false nếu không.
     */
    public function getSoldCodeDetailsForUser($recoveryCodeId, $userId) {
        $sql = "SELECT id, product_id, code_value, notes, sold_at 
                FROM recovery_codes 
                WHERE id = :id AND user_id_buyer = :user_id AND status = 'sold'";
        $this->db->query($sql);
        $this->db->bind(':id', (int)$recoveryCodeId, PDO::PARAM_INT);
        $this->db->bind(':user_id', (int)$userId, PDO::PARAM_INT);
        $codeDetails = $this->db->single();

        if ($codeDetails) {
            // TODO: Logic giải mã $codeDetails['code_value'] nếu nó đã được mã hóa
        }
        return $codeDetails;
    }

    /**
     * Đếm số lượng mã game có sẵn cho một loại sản phẩm.
     * @param int $productId
     * @return int
     */
    public function countAvailableCodesForProduct($productId) {
        $this->db->query("SELECT COUNT(id) as total FROM recovery_codes WHERE product_id = :product_id AND status = 'available'");
        $this->db->bind(':product_id', (int)$productId, PDO::PARAM_INT);
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    // --- Các hàm cho Admin Panel ---
    /**
     * (Admin) Lấy tất cả các mã game với tùy chọn lọc và phân trang.
     */
    public function adminGetAllGameCodes($limit = ADMIN_DEFAULT_ITEMS_PER_PAGE, $offset = 0, $filters = [], $orderBy = 'rc.created_at', $orderDir = 'DESC') {
        $sql = "SELECT rc.*, p.name as product_name, u.username as buyer_username 
                FROM recovery_codes rc
                JOIN products p ON rc.product_id = p.id
                LEFT JOIN users u ON rc.user_id_buyer = u.id";
        
        $whereClauses = [];
        $params = [];

        if (!empty($filters['product_id'])) { $whereClauses[] = "rc.product_id = :product_id_filter"; $params[':product_id_filter'] = (int)$filters['product_id']; }
        if (isset($filters['status']) && $filters['status'] !== '') { $whereClauses[] = "rc.status = :status_filter"; $params[':status_filter'] = $filters['status']; }
        // Sử dụng key filter nhất quán 'search_code_value'
        if (!empty($filters['search_code_value'])) { $whereClauses[] = "rc.code_value LIKE :search_code_value_filter"; $params[':search_code_value_filter'] = '%' . $filters['search_code_value'] . '%'; }
        if (!empty($filters['buyer_username_search'])) { $whereClauses[] = "u.username LIKE :buyer_username_filter"; $params[':buyer_username_filter'] = '%' . $filters['buyer_username_search'] . '%'; }


        if (!empty($whereClauses)) { $sql .= " WHERE " . implode(" AND ", $whereClauses); }

        $allowedOrderBy = ['rc.id', 'rc.code_value', 'p.name', 'rc.status', 'rc.created_at', 'rc.sold_at', 'u.username'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'rc.created_at';
        $orderDirSanitized = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$orderBySanitized} {$orderDirSanitized}";
        $sql .= " LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT); 
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * (Admin) Đếm tổng số mã game dựa trên bộ lọc.
     */
    public function adminGetTotalGameCodesCount($filters = []) {
        $sql = "SELECT COUNT(rc.id) as total 
                FROM recovery_codes rc
                JOIN products p ON rc.product_id = p.id
                LEFT JOIN users u ON rc.user_id_buyer = u.id";
        $whereClauses = []; $params = [];
        if (!empty($filters['product_id'])) { $whereClauses[] = "rc.product_id = :product_id_filter"; $params[':product_id_filter'] = (int)$filters['product_id']; }
        if (isset($filters['status']) && $filters['status'] !== '') { $whereClauses[] = "rc.status = :status_filter"; $params[':status_filter'] = $filters['status']; }
        // Sử dụng key filter nhất quán 'search_code_value'
        if (!empty($filters['search_code_value'])) { $whereClauses[] = "rc.code_value LIKE :search_code_value_filter"; $params[':search_code_value_filter'] = '%' . $filters['search_code_value'] . '%'; }
        if (!empty($filters['buyer_username_search'])) { $whereClauses[] = "u.username LIKE :buyer_username_filter"; $params[':buyer_username_filter'] = '%' . $filters['buyer_username_search'] . '%'; }

        if (!empty($whereClauses)) { $sql .= " WHERE " . implode(" AND ", $whereClauses); }
        
        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * (Admin) Lấy thông tin một mã game bằng ID.
     */
    public function adminGetGameCodeById($id) {
        $this->db->query("SELECT rc.*, p.name as product_name 
                          FROM recovery_codes rc
                          JOIN products p ON rc.product_id = p.id
                          WHERE rc.id = :id");
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        return $this->db->single();
    }

    /**
     * (Admin) Cập nhật thông tin một mã game.
     */
    public function adminUpdateGameCode($id, array $data) {
        $sql = "UPDATE recovery_codes SET 
                    product_id = :product_id, 
                    code_value = :code_value,
                    notes = :notes,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";
        
        $this->db->query($sql);
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        $this->db->bind(':product_id', $data['product_id'], PDO::PARAM_INT);
        $this->db->bind(':code_value', $data['code_value']);
        $this->db->bind(':notes', $data['notes'] ?? null);
        $this->db->bind(':status', $data['status']);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi cập nhật mã game: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false;
        }
    }

    /**
     * (Admin) Xóa một mã game.
     */
    public function adminDeleteGameCode($id) {
        $code = $this->adminGetGameCodeById($id); // Kiểm tra trước khi xóa
        if ($code && $code['status'] === 'sold') {
            write_log("Admin cố gắng xóa mã game đã bán ID: {$id}", "WARNING");
            return false; 
        }
        $this->db->query('DELETE FROM recovery_codes WHERE id = :id');
        $this->db->bind(':id', (int)$id, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi xóa mã game: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false;
        }
    }
}
?>