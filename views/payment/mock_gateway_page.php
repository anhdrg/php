<?php
// /views/payment/mock_gateway_page.php
// Dữ liệu: $data['orderCode'], $data['gatewayName'], $data['localTransactionId']
// $data['returnUrlSuccess'], $data['returnUrlCancel'], $data['webhookUrl']
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Thanh Toán Giả Lập - <?php echo htmlspecialchars($data['gatewayName']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .mock-container { max-width: 500px; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); text-align: center;}
        .logo { max-height: 50px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="mock-container">
        <?php if (strtolower($data['gatewayName']) == 'payos'): ?>
            <img src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL,'/').'/../assets/images/payos_logo.png' : '../assets/images/payos_logo.png'; // Cần điều chỉnh path nếu BASE_URL không đúng context ?>" alt="PayOS Logo" class="logo">
        <?php elseif (strtolower($data['gatewayName']) == 'sepay'): ?>
            <img src="<?php echo defined('BASE_URL') ? rtrim(BASE_URL,'/').'/../assets/images/sepay_logo.png' : '../assets/images/sepay_logo.png'; ?>" alt="SEPay Logo" class="logo">
        <?php endif; ?>

        <h4>Cổng Thanh Toán Giả Lập: <?php echo htmlspecialchars($data['gatewayName']); ?></h4>
        <p class="text-muted">Đây là trang giả lập để thử nghiệm luồng thanh toán.</p>
        
        <div class="alert alert-info">
            <p class="mb-1"><strong>Mã đơn hàng của bạn:</strong> <?php echo htmlspecialchars($data['orderCode']); ?></p>
            <p class="mb-0"><strong>Transaction ID (Hệ thống):</strong> #<?php echo htmlspecialchars($data['localTransactionId']); ?></p>
        </div>

        <p>Vui lòng chọn một hành động:</p>

        <div class="d-grid gap-2">
            <button onclick="simulateWebhookAndRedirect('<?php echo $data['webhookUrl']; ?>', 'PAID', '<?php echo $data['returnUrlSuccess']; ?>')" class="btn btn-success">
                Giả Lập Thanh Toán Thành Công (Webhook + Redirect)
            </button>
            <a href="<?php echo htmlspecialchars($data['returnUrlCancel']); ?>" class="btn btn-danger">
                Giả Lập Hủy Thanh Toán (Redirect)
            </a>
        </div>

        <p class="mt-3 small text-muted">
            <strong>Thông tin cho Developer:</strong><br>
            - Return URL (Thành công): <code><?php echo htmlspecialchars($data['returnUrlSuccess']); ?></code><br>
            - Return URL (Hủy): <code><?php echo htmlspecialchars($data['returnUrlCancel']); ?></code><br>
            - Webhook URL (sẽ được gọi ngầm): <code><?php echo htmlspecialchars($data['webhookUrl']); ?></code>
        </p>
    </div>

    <script>
    async function simulateWebhookAndRedirect(webhookUrl, status, redirectUrl) {
        const orderCode = "<?php echo htmlspecialchars($data['orderCode']); ?>";
        const localTransactionId = "<?php echo htmlspecialchars($data['localTransactionId']); ?>";
        const gatewayName = "<?php echo htmlspecialchars($data['gatewayName']); ?>";
        
        // Dữ liệu webhook giả lập (bạn cần điều chỉnh cho giống với PayOS/SEPay thật)
        let webhookData = new FormData();
        webhookData.append('orderCode', orderCode);
        webhookData.append('transactionId', localTransactionId); // Hoặc mã giao dịch của cổng
        webhookData.append('status', status); // 'PAID', 'CANCELLED', etc.
        webhookData.append('amount', "<?php /* Cần lấy amount từ PHP nếu có */ echo $this->transactionModel->getTransactionById($data['localTransactionId'])['amount'] ?? 0; ?>"); // Lấy amount
        webhookData.append('gateway', gatewayName);
        // Thêm các trường khác mà webhook thật sự gửi, ví dụ chữ ký giả (nếu webhook của bạn có check)
        // webhookData.append('signature', 'mock_signature_for_testing');


        try {
            // Gọi Webhook (giả lập server-to-server call)
            console.log("Đang giả lập gọi Webhook tới: ", webhookUrl);
            const response = await fetch(webhookUrl, {
                method: 'POST',
                body: webhookData
                // Headers: tùy theo webhook của bạn, có thể cần 'Content-Type': 'application/x-www-form-urlencoded' hoặc JSON
            });
            
            if (response.ok) {
                console.log("Webhook được gọi thành công, server phản hồi:", await response.text());
            } else {
                console.error("Lỗi gọi Webhook:", response.status, await response.text());
                alert("Có lỗi khi giả lập gọi Webhook. Kiểm tra Console.");
            }
        } catch (error) {
            console.error("Lỗi mạng hoặc lỗi khác khi gọi Webhook:", error);
            alert("Có lỗi mạng khi giả lập gọi Webhook. Kiểm tra Console.");
        } finally {
            // Chuyển hướng người dùng đến return URL sau khi webhook (giả lập) đã được gọi
            // Trong thực tế, cổng thanh toán sẽ tự redirect.
            // Ở đây, ta thêm một chút trễ để giả lập thời gian xử lý webhook.
            console.log("Sẽ chuyển hướng tới:", redirectUrl, "sau 2 giây.");
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 2000); 
        }
    }
    </script>
</body>
</html>