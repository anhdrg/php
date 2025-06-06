<?php
// /core/PaymentGatewayFactory.php

/**
 * Lớp Factory để tạo các đối tượng xử lý cổng thanh toán.
 * Giúp mã nguồn chính (ví dụ: PaymentController) không cần biết chi tiết về việc khởi tạo từng loại cổng.
 */
class PaymentGatewayFactory {
    /**
     * Tạo một instance của lớp xử lý cổng thanh toán dựa trên tên.
     * @param string $gatewayName Tên của cổng thanh toán (ví dụ: 'PayOS', 'SEPay', 'Timo Bank').
     * @return PaymentGatewayInterface Một đối tượng implement PaymentGatewayInterface.
     * @throws Exception Nếu không tìm thấy cổng hoặc lớp xử lý tương ứng.
     */
    public static function create(string $gatewayName) {
        $paymentGatewayModel = new PaymentGatewayModel();
        // Lấy cấu hình của cổng từ CSDL (bao gồm API keys, settings,...)
        $config = $paymentGatewayModel->getGatewayByName($gatewayName);

        if (!$config) {
            throw new Exception("Cổng thanh toán '{$gatewayName}' không được tìm thấy trong cơ sở dữ liệu.");
        }
        
        if (!$config['is_active']) {
             throw new Exception("Cổng thanh toán '{$gatewayName}' hiện không hoạt động.");
        }
        
        // Dựa vào tên cổng để quyết định tạo đối tượng nào
        switch ($gatewayName) {
            case 'PayOS':
                if (!class_exists('PayOSGateway')) throw new Exception("Class 'PayOSGateway' không tìm thấy.");
                return new PayOSGateway($config);

            case 'SEPay':
                if (!class_exists('SEPayGateway')) throw new Exception("Class 'SEPayGateway' không tìm thấy.");
                return new SEPayGateway($config);

            case 'Timo Bank': // Thêm case cho cổng nạp thủ công
                if (!class_exists('TimoGateway')) throw new Exception("Class 'TimoGateway' không tìm thấy.");
                return new TimoGateway($config);

            default:
                throw new Exception("Không tìm thấy lớp xử lý cho cổng thanh toán '{$gatewayName}'.");
        }
    }
}
?>