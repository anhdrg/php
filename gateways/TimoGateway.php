<?php
// /gateways/TimoGateway.php

class TimoGateway implements PaymentGatewayInterface {
    private $config;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getName(): string {
        return 'Timo Bank';
    }

    /**
     * Tạo mảng chứa thông tin để hiển thị QR code cho việc chuyển khoản.
     */
    public function createPaymentRequest(array $data): array {
        // ĐÃ SỬA: Thêm json_decode để chuyển chuỗi settings từ CSDL thành mảng PHP
        $settings = !empty($this->config['settings']) ? json_decode($this->config['settings'], true) : null;
        
        // Kiểm tra các thông tin cấu hình cần thiết từ mảng $settings đã được decode
        if (empty($settings) || empty($settings['bank_id']) || empty($settings['account_number']) || empty($settings['account_name'])) {
            throw new Exception("Cấu hình cho Timo Bank chưa đầy đủ (thiếu bank_id, số tài khoản, hoặc tên tài khoản). Vui lòng kiểm tra trong trang quản trị.");
        }

        // Tạo URL ảnh QR dựa trên template VietQR
        $qrTemplate = "https://img.vietqr.io/image/{BANK_ID}-{ACCOUNT_NO}-compact.png";
        $qrImageUrl = str_replace(
            ['{BANK_ID}', '{ACCOUNT_NO}'],
            [$settings['bank_id'], $settings['account_number']],
            $qrTemplate
        );
        
        // Thêm các tham số vào URL ảnh QR
        $queryParams = [
            'amount' => $data['amount'],
            'addInfo' => $data['description'], // Nội dung chuyển khoản
            'accountName' => $settings['account_name']
        ];
        $qrImageUrl .= '?' . http_build_query($queryParams);

        // Trả về một cấu trúc dữ liệu nhất quán
        return [
            'is_manual_confirmation' => true, // Đánh dấu rằng việc xác nhận giao dịch là thủ công
            'qr_code_url' => $qrImageUrl,      // URL của ảnh QR
            'checkout_url' => $qrImageUrl,     // Cho cổng QR, checkout_url có thể là chính ảnh QR
            'amount' => $data['amount'],
            'content' => $data['description']
        ];
    }
    
    /**
     * Cổng thủ công không có webhook tự động.
     */
    public function handleWebhook(array $webhookData): array {
        // Trả về thông báo rằng đây là xử lý thủ công
        return [
            'success' => true,
            'message' => 'Timo Bank là cổng thủ công, không xử lý webhook.'
        ];
    }
    
    /**
     * Cổng thủ công không có webhook nên không cần xác thực chữ ký.
     */
    public function verifyWebhookSignature(array $webhookData, string $signature): bool {
        return true;
    }
}
?>