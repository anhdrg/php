<?php
// /models/PaymentGatewayModel.php

class PaymentGatewayModel {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Lấy tất cả các cổng thanh toán.
     * @param bool $onlyActive Chỉ lấy các cổng đang hoạt động. Mặc định là false (lấy tất cả).
     * @return array Danh sách các cổng thanh toán.
     */
    public function getAllGateways($onlyActive = false) {
        $sql = "SELECT * FROM payment_gateways";
        if ($onlyActive) {
            $sql .= " WHERE is_active = TRUE";
        }
        $sql .= " ORDER BY name ASC";
        
        $this->db->query($sql);
        return $this->db->resultSet();
    }

    /**
     * Lấy thông tin một cổng thanh toán bằng ID.
     * @param int $id ID của cổng thanh toán.
     * @return mixed Object cổng thanh toán nếu tìm thấy, false nếu không.
     */
    public function getGatewayById($id) {
        $this->db->query("SELECT * FROM payment_gateways WHERE id = :id");
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        return $this->db->single();
    }
    
    /**
     * Lấy thông tin một cổng thanh toán bằng tên (ví dụ 'PayOS', 'SEPay').
     * @param string $name Tên của cổng thanh toán.
     * @return mixed Object cổng thanh toán nếu tìm thấy, false nếu không.
     */
    public function getGatewayByName($name) {
        $this->db->query("SELECT * FROM payment_gateways WHERE name = :name");
        $this->db->bind(':name', $name);
        return $this->db->single();
    }


    /**
     * Cập nhật thông tin một cổng thanh toán.
     * @param int $id ID của cổng thanh toán.
     * @param array $data Dữ liệu cần cập nhật (ví dụ: client_id, api_key, checksum_key, merchant_id, is_active, description, logo_url).
     * @return bool True nếu thành công, false nếu thất bại.
     */
    public function updateGateway($id, $data) {
        $sql = "UPDATE payment_gateways SET 
                    client_id = :client_id, 
                    api_key = :api_key, 
                    checksum_key = :checksum_key,
                    merchant_id = :merchant_id,
                    is_active = :is_active,
                    description = :description,
                    logo_url = :logo_url,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id";

        $this->db->query($sql);
        $this->db->bind(':id', $id, PDO::PARAM_INT);
        $this->db->bind(':client_id', $data['client_id'] ?? null);
        $this->db->bind(':api_key', $data['api_key'] ?? null);
        $this->db->bind(':checksum_key', $data['checksum_key'] ?? null);
        $this->db->bind(':merchant_id', $data['merchant_id'] ?? null);
        $this->db->bind(':is_active', (bool)($data['is_active'] ?? 0), PDO::PARAM_BOOL);
        $this->db->bind(':description', $data['description'] ?? null);
        $this->db->bind(':logo_url', $data['logo_url'] ?? null);
        // Không cho phép sửa 'name' qua form này để đảm bảo tính nhất quán

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi cập nhật cổng thanh toán: " . $e->getMessage() . " | ID: {$id}", 'ERROR');
            return false;
        }
    }

    // Admin thường không thêm cổng thanh toán mới qua giao diện vì việc tích hợp cần code.
    // Nếu cần, có thể thêm hàm addGateway(), nhưng cần cẩn thận.
}
?>