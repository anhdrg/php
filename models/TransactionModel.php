<?php
// /models/TransactionModel.php

class TransactionModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Tạo một giao dịch mới.
     * @param array $data Dữ liệu giao dịch.
     * @return string|false ID của giao dịch mới nếu thành công, false nếu thất bại.
     */
    public function createTransaction($data) {
        $sql = "INSERT INTO transactions (user_id, product_id, recovery_code_id, transaction_type, amount, payment_method, payment_gateway_txn_id, status, description, created_at)
                VALUES (:user_id, :product_id, :recovery_code_id, :transaction_type, :amount, :payment_method, :payment_gateway_txn_id, :status, :description, NOW())";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', $data['user_id'], PDO::PARAM_INT);
        $this->db->bind(':product_id', $data['product_id'] ?? null, PDO::PARAM_INT);
        $this->db->bind(':recovery_code_id', $data['recovery_code_id'] ?? null, PDO::PARAM_INT);
        $this->db->bind(':transaction_type', $data['transaction_type']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':payment_method', $data['payment_method'] ?? null);
        $this->db->bind(':payment_gateway_txn_id', $data['payment_gateway_txn_id'] ?? null);
        $this->db->bind(':status', $data['status'] ?? TRANSACTION_STATUS_PENDING);
        $this->db->bind(':description', $data['description'] ?? null);

        try {
            if ($this->db->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            write_log("Lỗi tạo giao dịch: " . $e->getMessage() . " | Data: " . json_encode($data), 'ERROR');
            return false;
        }
    }

    /**
     * Cập nhật trạng thái của một giao dịch.
     * @param int $transactionId ID giao dịch.
     * @param string $status Trạng thái mới.
     * @param string|null $paymentGatewayTxnId Mã giao dịch từ cổng thanh toán (nếu có).
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function updateTransactionStatus($transactionId, $status, $paymentGatewayTxnId = null) {
        $sql = "UPDATE transactions SET status = :status, updated_at = CURRENT_TIMESTAMP";
        $paramsToBind = [
            ':transaction_id' => (int)$transactionId,
            ':status' => $status
        ];
        if ($paymentGatewayTxnId !== null) {
            $sql .= ", payment_gateway_txn_id = :payment_gateway_txn_id";
            $paramsToBind[':payment_gateway_txn_id'] = $paymentGatewayTxnId;
        }
        $sql .= " WHERE id = :transaction_id";
        $this->db->query($sql);
        foreach ($paramsToBind as $key => $value) { $this->db->bind($key, $value); }
        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi cập nhật trạng thái giao dịch: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Lấy thông tin một giao dịch bằng ID của nó.
     */
    public function getTransactionById($transactionId) {
        $this->db->query("SELECT * FROM transactions WHERE id = :id");
        $this->db->bind(':id', (int)$transactionId, PDO::PARAM_INT);
        return $this->db->single();
    }
    
    /**
     * Lấy thông tin một giao dịch bằng mã giao dịch của cổng thanh toán.
     */
    public function getTransactionByGatewayId($gatewayTxnId) {
        $this->db->query("SELECT * FROM transactions WHERE payment_gateway_txn_id = :gateway_txn_id");
        $this->db->bind(':gateway_txn_id', $gatewayTxnId);
        return $this->db->single();
    }

    /**
     * Lấy lịch sử tất cả giao dịch của một người dùng.
     */
    public function getUserTransactions($userId, $limit = DEFAULT_ITEMS_PER_PAGE, $offset = 0) {
        $sql = "SELECT t.*, p.name as product_type_name 
                FROM transactions t
                LEFT JOIN products p ON t.product_id = p.id 
                WHERE t.user_id = :user_id
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";
        $this->db->query($sql);
        $this->db->bind(':user_id', (int)$userId, PDO::PARAM_INT);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số giao dịch của một người dùng.
     */
    public function getTotalUserTransactionsCount($userId) {
        $this->db->query("SELECT COUNT(id) as total FROM transactions WHERE user_id = :user_id");
        $this->db->bind(':user_id', (int)$userId, PDO::PARAM_INT);
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Lấy lịch sử các đơn hàng đã mua thành công của một người dùng.
     */
    public function getUserPurchaseHistory($userId, $limit = DEFAULT_ITEMS_PER_PAGE, $offset = 0) {
        $sql = "SELECT 
                    t.id as transaction_id, t.amount, t.created_at as purchase_date, 
                    p.name as product_type_name, 
                    (SELECT mf.file_path FROM media_files mf WHERE mf.id = p.main_image_media_id) as product_type_image,
                    rc.code_value as game_code_data, rc.id as recovery_code_internal_id
                FROM transactions t
                JOIN products p ON t.product_id = p.id
                LEFT JOIN recovery_codes rc ON t.recovery_code_id = rc.id
                WHERE t.user_id = :user_id 
                  AND t.transaction_type = :purchase_type 
                  AND t.status = :completed_status
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', (int)$userId, PDO::PARAM_INT);
        $this->db->bind(':purchase_type', TRANSACTION_TYPE_PURCHASE);
        $this->db->bind(':completed_status', TRANSACTION_STATUS_COMPLETED);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        return $this->db->resultSet();
    }

    /**
     * Đếm tổng số đơn hàng đã mua thành công của một người dùng.
     */
    public function getTotalUserPurchaseHistoryCount($userId) {
        $sql = "SELECT COUNT(t.id) as total
                FROM transactions t
                WHERE t.user_id = :user_id 
                  AND t.transaction_type = :purchase_type 
                  AND t.status = :completed_status 
                  AND t.recovery_code_id IS NOT NULL";
        
        $this->db->query($sql);
        $this->db->bind(':user_id', (int)$userId, PDO::PARAM_INT);
        $this->db->bind(':purchase_type', TRANSACTION_TYPE_PURCHASE);
        $this->db->bind(':completed_status', TRANSACTION_STATUS_COMPLETED);
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }


    // --- Các hàm cho Admin ---

    /**
     * (Private) Hàm helper để xây dựng mệnh đề WHERE và tham số cho các bộ lọc của admin.
     */
    private function buildAdminFilters($filters = []) {
        $whereClauses = [];
        $params = [];
        if (!empty($filters['user_id'])) { $whereClauses[] = "t.user_id = :user_id_filter"; $params[':user_id_filter'] = (int)$filters['user_id']; }
        if (!empty($filters['status'])) { $whereClauses[] = "t.status = :status_filter"; $params[':status_filter'] = $filters['status']; }
        if (!empty($filters['transaction_type'])) { $whereClauses[] = "t.transaction_type = :type_filter"; $params[':type_filter'] = $filters['transaction_type']; }
        if (!empty($filters['payment_method'])) { $whereClauses[] = "t.payment_method = :payment_filter"; $params[':payment_filter'] = $filters['payment_method']; }
        if (!empty($filters['date_from'])) { $whereClauses[] = "DATE(t.created_at) >= :date_from_filter"; $params[':date_from_filter'] = $filters['date_from']; }
        if (!empty($filters['date_to'])) { $whereClauses[] = "DATE(t.created_at) <= :date_to_filter"; $params[':date_to_filter'] = $filters['date_to']; }
        if (!empty($filters['search_term'])) {
            $whereClauses[] = "(t.id LIKE :search_term OR u.username LIKE :search_term OR p.name LIKE :search_term OR t.payment_gateway_txn_id LIKE :search_term OR t.description LIKE :search_term)";
            $params[':search_term'] = '%' . $filters['search_term'] . '%';
        }
        $sqlWhere = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";
        return ['sql' => $sqlWhere, 'params' => $params];
    }
    
    /**
     * (Admin) Đếm tổng số giao dịch dựa trên bộ lọc.
     */
    public function adminGetTotalTransactionsCount($filters = []) {
        $filterData = $this->buildAdminFilters($filters);
        $sql = "SELECT COUNT(t.id) as total FROM transactions t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN products p ON t.product_id = p.id" . $filterData['sql'];
        $this->db->query($sql);
        foreach ($filterData['params'] as $key => $value) { $this->db->bind($key, $value); }
        $result = $this->db->single();
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * (Admin) Lấy tất cả giao dịch với phân trang và bộ lọc.
     */
    public function adminGetAllTransactions($limit = ADMIN_DEFAULT_ITEMS_PER_PAGE, $offset = 0, $filters = [], $orderBy = 't.created_at', $orderDir = 'DESC') {
        $filterData = $this->buildAdminFilters($filters);
        $sql = "SELECT t.*, u.username as user_username, p.name as product_type_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN products p ON t.product_id = p.id" . $filterData['sql'];
        
        $allowedOrderBy = ['t.id', 'u.username', 't.amount', 't.status', 't.created_at'];
        $orderBySanitized = in_array($orderBy, $allowedOrderBy) ? $orderBy : 't.created_at';
        $orderDirSanitized = strtoupper($orderDir) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY {$orderBySanitized} {$orderDirSanitized}";
        $sql .= " LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        $params = $filterData['params'];
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        
        return $this->db->resultSet();
    }

    /**
     * (Admin) Tính tổng doanh thu từ các giao dịch mua hàng đã hoàn thành.
     */
    public function adminGetTotalRevenue() {
        $sql = "SELECT SUM(amount) as total_revenue FROM transactions WHERE status = :status AND transaction_type = :type";
        $this->db->query($sql);
        $this->db->bind(':status', TRANSACTION_STATUS_COMPLETED);
        $this->db->bind(':type', TRANSACTION_TYPE_PURCHASE);
        $result = $this->db->single();
        return $result && $result['total_revenue'] ? (float)$result['total_revenue'] : 0;
    }

    /**
     * (Admin) Lấy các giao dịch gần đây cho dashboard.
     */
    public function adminGetRecentTransactions($limit = 5) {
        $sql = "SELECT t.id, t.amount, t.status, t.created_at, t.transaction_type, t.user_id, u.username as user_username, p.name as product_type_name FROM transactions t LEFT JOIN users u ON t.user_id = u.id LEFT JOIN products p ON t.product_id = p.id ORDER BY t.created_at DESC LIMIT :limit";
        $this->db->query($sql);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->resultSet();
    }
    
    /**
     * Tìm một giao dịch dựa trên mô tả (nội dung chuyển khoản).
     * Dùng để đảm bảo mã giao dịch là duy nhất.
     */
    public function findTransactionByDescription($description) {
        $this->db->query("SELECT * FROM transactions WHERE description = :description LIMIT 1");
        $this->db->bind(':description', $description);
        return $this->db->single();
    }
}
?>