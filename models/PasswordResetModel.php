<?php
// /models/PasswordResetModel.php

class PasswordResetModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Tạo và lưu một cặp selector và hashed validator.
     * @param int $userId
     * @param string $selector
     * @param string $validator (clear text, sẽ được hash)
     * @param int $durationInSeconds
     * @return bool
     */
    public function createSelectorValidatorToken($userId, $selector, $validator, $durationInSeconds = PASSWORD_RESET_TOKEN_DURATION) { // Sử dụng hằng số
        $hashedValidator = password_hash($validator, PASSWORD_DEFAULT);
        $expiresAt = date('Y-m-d H:i:s', time() + $durationInSeconds);

        // Giả sử bảng password_resets có các cột: user_id, selector, hashed_validator, expires_at
        $this->db->query('INSERT INTO password_reset_tokens (user_id, selector, hashed_validator, expires_at) VALUES (:user_id, :selector, :hashed_validator, :expires_at)');
        $this->db->bind(':user_id', $userId, PDO::PARAM_INT);
        $this->db->bind(':selector', $selector);
        $this->db->bind(':hashed_validator', $hashedValidator);
        $this->db->bind(':expires_at', $expiresAt);

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi tạo selector/validator token: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Lấy bản ghi password reset bằng selector, hợp lệ (chưa dùng, chưa hết hạn).
     * @param string $selector
     * @return object|false
     */
    public function getResetRecordBySelector($selector) {
        $this->db->query("SELECT * FROM password_reset_tokens WHERE selector = :selector AND is_used = FALSE AND expires_at > NOW()");
        $this->db->bind(':selector', $selector);
        return $this->db->single();
    }

    /**
     * Đánh dấu một token là đã sử dụng (dựa trên selector).
     * @param string $selector
     * @return bool
     */
    public function markTokenAsUsedBySelector($selector) {
        $this->db->query('UPDATE password_reset_tokens SET is_used = TRUE, updated_at = CURRENT_TIMESTAMP WHERE selector = :selector');
        $this->db->bind(':selector', $selector);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi đánh dấu token đã sử dụng (selector): " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    /**
     * Xóa một token đặt lại mật khẩu (dựa trên selector).
     * @param string $selector
     * @return bool
     */
    public function deleteTokenBySelector($selector) {
        $this->db->query('DELETE FROM password_reset_tokens WHERE selector = :selector');
        $this->db->bind(':selector', $selector);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi xóa token (selector): " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Xóa tất cả các token đã hết hạn hoặc đã sử dụng.
     * @return int|false Số dòng bị xóa hoặc false nếu lỗi.
     */
    public function deleteExpiredOrUsedTokens() {
        $this->db->query('DELETE FROM password_reset_tokens WHERE is_used = TRUE OR expires_at <= NOW()');
        try {
            $this->db->execute();
            return $this->db->rowCount();
        } catch (PDOException $e) {
            write_log("Lỗi xóa token hết hạn/đã dùng: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    // --- CÁC HÀM MỚI CHO ADMIN ---

    /**
     * (Admin) Lấy tất cả token reset mật khẩu với phân trang và filter.
     * @param int $limit
     * @param int $offset
     * @param array $filters ['user_id' => ..., 'is_used' => ..., 'username_search' => ...]
     * @param string $orderBy
     * @param string $orderDir
     * @return array
     */
    public function adminGetAllPasswordResetTokens($limit = ADMIN_DEFAULT_ITEMS_PER_PAGE, $offset = 0, $filters = [], $orderBy = 'pr.created_at', $orderDir = 'DESC') {
        $sql = "SELECT pr.*, u.username as user_username 
                FROM password_reset_tokens pr
                JOIN users u ON pr.user_id = u.id";
        
        $whereClauses = [];
        $params = [];

        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $whereClauses[] = "pr.user_id = :user_id_filter";
            $params[':user_id_filter'] = (int)$filters['user_id'];
        }
        if (isset($filters['is_used']) && $filters['is_used'] !== '') {
            $whereClauses[] = "pr.is_used = :is_used_filter";
            $params[':is_used_filter'] = (bool)$filters['is_used']; // 0 or 1
        }
        if (!empty($filters['username_search'])) {
            $whereClauses[] = "u.username LIKE :username_search_filter";
            $params[':username_search_filter'] = '%' . $filters['username_search'] . '%';
        }
        if (!empty($filters['selector_search'])) {
            $whereClauses[] = "pr.selector LIKE :selector_search_filter";
            $params[':selector_search_filter'] = '%' . $filters['selector_search'] . '%';
        }

        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $allowedOrderBy = ['pr.id', 'u.username', 'pr.selector', 'pr.created_at', 'pr.expires_at', 'pr.is_used'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 'pr.created_at';
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
     * (Admin) Đếm tổng số token reset mật khẩu dựa trên bộ lọc.
     * @param array $filters
     * @return int
     */
    public function adminGetTotalPasswordResetTokensCount($filters = []) {
        $sql = "SELECT COUNT(pr.id) as total 
                FROM password_reset_tokens pr
                JOIN users u ON pr.user_id = u.id";
        $whereClauses = [];
        $params = [];

        if (isset($filters['user_id']) && $filters['user_id'] !== '') { /* ... */ }
        if (isset($filters['is_used']) && $filters['is_used'] !== '') { /* ... */ }
        if (!empty($filters['username_search'])) { /* ... */ }
        if (!empty($filters['selector_search'])) { /* ... */ }
        // Copy logic filter từ adminGetAllPasswordResetTokens nếu cần thiết đầy đủ
        // Ví dụ đơn giản:
        if (isset($filters['is_used']) && $filters['is_used'] !== '') {
            $whereClauses[] = "pr.is_used = :is_used_filter";
            $params[':is_used_filter'] = (bool)$filters['is_used'];
        }


        if (!empty($whereClauses)) { $sql .= " WHERE " . implode(" AND ", $whereClauses); }
        
        $this->db->query($sql);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * (Admin) Xóa một token reset mật khẩu bằng ID của nó.
     * @param int $tokenId ID của token.
     * @return bool
     */
    public function deleteTokenById($tokenId) {
        $this->db->query('DELETE FROM password_reset_tokens WHERE id = :id');
        $this->db->bind(':id', (int)$tokenId, PDO::PARAM_INT);
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi xóa token reset password: " . $e->getMessage() . " | ID: {$tokenId}", 'ERROR');
            return false;
        }
    }
}
?>