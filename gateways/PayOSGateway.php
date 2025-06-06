<?php
// /gateways/PayOSGateway.php

class PayOSGateway implements PaymentGatewayInterface {
    private $clientId;
    private $apiKey;
    private $checksumKey;

    public function __construct($config) {
        $this->clientId = $config['client_id'] ?? '';
        $this->apiKey = $config['api_key'] ?? '';
        $this->checksumKey = $config['checksum_key'] ?? '';
    }
    
    public function getName(): string {
        return 'PayOS';
    }

    public function createPaymentRequest(array $data): array {
        if (empty($this->clientId) || empty($this->apiKey) || empty($this->checksumKey)) {
            throw new Exception("Cấu hình PayOS (Client ID, API Key, Checksum Key) không được để trống.");
        }

        $payosData = [
            "orderCode" => (int) $data['order_id'],
            "amount" => (int) $data['amount'],
            "description" => $data['description'],
            "returnUrl" => $data['return_url'],
            "cancelUrl" => $data['cancel_url']
        ];

        $signature = $this->createSignature($payosData);
        $payosData['signature'] = $signature;

        $ch = curl_init('https://api-merchant.payos.vn/v2/payment-requests');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payosData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'x-client-id: ' . $this->clientId,
            'x-api-key: ' . $this->apiKey
        ]);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode == 200 || $httpcode == 201) {
            $responseData = json_decode($response, true);
            if (isset($responseData['data']['checkoutUrl'])) {
                return [
                    'is_manual' => false,
                    'checkout_url' => $responseData['data']['checkoutUrl'],
                    'qr_code_url' => $responseData['data']['qrCode']
                ];
            }
        }
        
        write_log("Lỗi tạo link PayOS: " . $response, "ERROR");
        throw new Exception("Không thể tạo link thanh toán PayOS. Phản hồi từ PayOS: " . $response);
    }
    
    public function handleWebhook(array $webhookData): array {
        // Logic xử lý webhook được đặt trong PaymentController::webhook()
        // Hàm này có thể trả về thông tin đã được chuẩn hóa nếu cần
        if (!isset($webhookData['description']) || !isset($webhookData['amount'])) {
            return ['success' => false, 'message' => 'Webhook PayOS thiếu dữ liệu.'];
        }
        return [
            'success' => true,
            'transaction_id' => $webhookData['orderCode'],
            'amount' => (float)$webhookData['amount'],
            'content' => $webhookData['description'],
            'gateway_txn_id' => $webhookData['paymentLinkId'] ?? null
        ];
    }
    
    public function verifyWebhookSignature(array $webhookData, string $signature): bool {
        $dataToSign = [];
        // Lấy các trường cần thiết để tạo chữ ký từ PayOS
        $fields = ['orderCode', 'amount', 'description', 'accountNumber', 'reference', 'transactionDateTime'];
        foreach($fields as $field) {
            if (isset($webhookData[$field])) {
                $dataToSign[$field] = $webhookData[$field];
            }
        }
        $calculatedSignature = $this->createSignature($dataToSign);
        return $calculatedSignature === $signature;
    }

    private function createSignature(array $data): string {
        ksort($data);
        $dataQuery = http_build_query($data);
        return hash_hmac('sha256', $dataQuery, $this->checksumKey);
    }
}
?>