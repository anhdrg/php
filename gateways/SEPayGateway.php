<?php
// /gateways/SEPayGateway.php

class SEPayGateway implements PaymentGatewayInterface {
    private $config;

    public function __construct(array $config) {
        $this->config = $config; // $config['api_key'], $config['merchant_id'] (hoặc partner_id)
    }

    public function getName(): string {
        return 'SEPay';
    }

    public function createPaymentRequest(array $orderData): array {
        // --- LOGIC TẠO YÊU CẦU THANH TOÁN SEPAY ---
        // **BẠN CẦN THAY THẾ BẰNG LOGIC GỌI API SEPAY THỰC TẾ**
        // $partnerId = $this->config['merchant_id'];
        // $apiKey = $this->config['api_key'];
        // $requestId = $orderData['orderCode'];
        // $amount = $orderData['amount'];
        // $orderInfo = $orderData['description'];
        // $returnUrl = $orderData['returnUrl'];
        // $notifyUrl = $orderData['notifyUrl']; // QUAN TRỌNG
        // $extraData = json_encode(['local_txn_id' => $orderData['localTransactionId']]); // Ví dụ
        // $requestType = "captureWallet"; // Hoặc type phù hợp

        // Tạo chuỗi dữ liệu để ký theo quy tắc của SEPay
        // $dataToSign = $partnerId.'&'.$requestId.'&'.$amount.'&'.$orderInfo.'&'.$returnUrl.'&'.$notifyUrl.'&'.$extraData.'&'.$requestType;
        // $signature = hash_hmac('sha256', $dataToSign, $apiKey);

        // $sepayApiUrl = "https://api.sepay.vn/..."; // URL API tạo yêu cầu của SEPay
        // $payload = [
        //     'partnerId' => $partnerId,
        //     'requestId' => $requestId,
        //     // ... các tham số khác ...
        //     'signature' => $signature
        // ];
        
        // Có thể là redirect GET đến URL của SEPay với các tham số
        // $redirectUrl = $sepayApiUrl . "?" . http_build_query($payload);
        // return ['type' => 'redirect', 'url' => $redirectUrl];
        
        // Hoặc SEPay trả về JSON chứa link thanh toán, hoặc cần POST form...
        // --- KẾT THÚC LOGIC SEPAY ---

        // --- PLACEHOLDER --- (Xóa khi tích hợp thật)
        write_log("[SEPayGateway] Create Payment Request (Placeholder): " . json_encode($orderData), "INFO");
        $mockRedirectUrl = base_url('payment/mock-gateway-page/' . $orderData['orderCode'] . '/' . $this->getName());
        return ['type' => 'redirect', 'url' => $mockRedirectUrl];
        // --- END PLACEHOLDER ---
    }

    public function handleWebhook(array $webhookData, array $serverHeaders): array {
        // --- LOGIC XỬ LÝ WEBHOOK SEPAY ---
        // **BẠN CẦN THAY THẾ BẰNG LOGIC XÁC THỰC VÀ XỬ LÝ WEBHOOK SEPAY THỰC TẾ**
        // 1. Lấy dữ liệu và chữ ký từ SEPay (qua POST, GET, hoặc header)
        // $signatureFromSEPay = $webhookData['signature'] ?? ($serverHeaders['X-SEPay-Signature'] ?? '');
        // 2. Lấy Secret Key (API Key) của SEPay từ config
        // $secretKey = $this->config['api_key'];
        // 3. Tạo lại chuỗi dữ liệu để xác thực chữ ký theo quy tắc của SEPay
        // $dataToVerify = $webhookData; unset($dataToVerify['signature']); ksort($dataToVerify); // Ví dụ
        // $stringToHash = ... ;
        // $calculatedSignature = hash_hmac('sha256', $stringToHash, $secretKey);
        // 4. So sánh chữ ký
        // if (!hash_equals($calculatedSignature, $signatureFromSEPay)) {
        //     return ['is_signature_valid' => false, 'status' => 'error', 'message' => 'Invalid SEPay signature'];
        // }

        // --- XỬ LÝ WEBHOOK GIẢ LẬP TỪ MOCK PAGE --- (Xóa khi tích hợp thật)
        write_log("[SEPayGateway Webhook Mock] Received Data: " . json_encode($webhookData), "INFO");
        $isSignatureValid = true; // Giả lập chữ ký hợp lệ
        $orderCode = $webhookData['orderCode'] ?? ($webhookData['requestId'] ?? null); // SEPay có thể dùng requestId
        $gatewayStatus = strtoupper($webhookData['status'] ?? ''); // Ví dụ: 'SUCCESS', 'FAIL', 'PENDING'
        $gatewayTxnId = $webhookData['transactionId'] ?? ('sepay_wh_' . time());
        $amountPaid = $webhookData['amount'] ?? 0;
        // --- KẾT THÚC XỬ LÝ WEBHOOK GIẢ LẬP ---

        if (!$isSignatureValid) { // Thay bằng logic xác thực thật
            return ['is_signature_valid' => false, 'status' => 'error', 'message' => 'Invalid signature'];
        }

        $internalStatus = TRANSACTION_STATUS_PENDING;
        // Ánh xạ trạng thái của SEPay sang trạng thái nội bộ của bạn
        if ($gatewayStatus === 'SUCCESS' || $gatewayStatus === 'COMPLETED' || $gatewayStatus === 'PAID') { // Kiểm tra tài liệu SEPay
            $internalStatus = TRANSACTION_STATUS_COMPLETED;
        } elseif ($gatewayStatus === 'CANCELLED') {
            $internalStatus = TRANSACTION_STATUS_CANCELLED;
        } elseif ($gatewayStatus === 'FAIL' || $gatewayStatus === 'FAILED' || $gatewayStatus === 'ERROR') {
            $internalStatus = TRANSACTION_STATUS_FAILED;
        }

        return [
            'is_signature_valid' => true,
            'status' => $internalStatus,
            'order_code' => $orderCode,
            'gateway_txn_id' => $gatewayTxnId,
            'amount_paid' => (float)$amountPaid,
            'message' => "SEPay Webhook processed with status: {$gatewayStatus}"
        ];
        // --- KẾT THÚC LOGIC SEPAY ---
    }

    public function processReturn(array $requestData): array {
        // Xử lý dữ liệu SEPay gửi về trên returnUrl
        // Tương tự PayOS, không nên dựa vào đây để cập nhật trạng thái cuối cùng.
        // Cần kiểm tra tài liệu của SEPay về các tham số họ gửi về.
        
        $orderCode = $requestData['requestId'] ?? ($requestData['orderCode'] ?? null);
        $status = $requestData['status'] ?? null; // Ví dụ
        $message = $requestData['message'] ?? '';
        
        $internalStatus = 'pending';
        if (strtoupper($status) === 'SUCCESS') {
            $internalStatus = 'success';
            $message = $message ?: 'Giao dịch đang được SEPay xử lý.';
        } elseif (strtoupper($status) === 'CANCELLED' || strtoupper($status) === 'FAIL') {
            $internalStatus = 'cancelled'; // hoặc 'error'
            $message = $message ?: 'Giao dịch đã bị hủy hoặc không thành công.';
        } else {
            $internalStatus = 'error';
            $message = $message ?: 'Trạng thái giao dịch không xác định.';
        }
        
        return [
            'status' => $internalStatus,
            'order_code' => $orderCode,
            'message' => $message,
            'gateway_txn_id' => $requestData['transactionId'] ?? null // Mã giao dịch của SEPay
        ];
    }
}