<?php
// /interfaces/PaymentGatewayInterface.php

interface PaymentGatewayInterface {
    /**
     * Khởi tạo gateway với cấu hình từ CSDL.
     * @param array $config Mảng chứa thông tin cấu hình (api_key, client_id, settings,...)
     */
    public function __construct(array $config);

    /**
     * Lấy tên của cổng thanh toán.
     * @return string Tên cổng (ví dụ: "PayOS", "Timo Bank").
     */
    public function getName(): string;

    /**
     * Tạo yêu cầu thanh toán.
     * Trả về một mảng chứa thông tin cần thiết để xử lý thanh toán (URL thanh toán, mã QR, hướng dẫn,...).
     * @param array $data Dữ liệu giao dịch (amount, order_id, description, returnUrl, cancelUrl).
     * @return array Mảng chứa thông tin thanh toán.
     */
    public function createPaymentRequest(array $data): array;

    /**
     * Xử lý dữ liệu từ webhook.
     * @param array $webhookData Dữ liệu mà cổng thanh toán gửi đến.
     * @return array Kết quả xử lý (ví dụ: ['success' => true, 'message' => 'Giao dịch đã xử lý']).
     */
    public function handleWebhook(array $webhookData): array;
    
    /**
     * Xác thực chữ ký của webhook (nếu có).
     * @param array $webhookData Dữ liệu từ webhook (chưa bao gồm signature).
     * @param string $signature Chữ ký từ header hoặc trong body.
     * @return bool True nếu hợp lệ, false nếu không.
     */
    public function verifyWebhookSignature(array $webhookData, string $signature): bool;
}
?>