<?php
// /views/display-qr.php
// Dữ liệu được truyền từ PaymentController::display_qr():
// $data['payment_info'] chứa ['qr_code_url', 'amount', 'content']
$payment_info = $data['payment_info'] ?? null;
?>
<div class="container mt-5">
    <?php if ($payment_info): ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card text-center shadow">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-qrcode me-2"></i>Quét Mã QR Để Hoàn Tất Nạp Tiền</h4>
                    </div>
                    <div class="card-body p-4">
                        <p>Vui lòng mở ứng dụng ngân hàng hoặc ví điện tử của bạn và quét mã QR dưới đây.</p>
                        
                        <div class="qr-code-container my-4 text-center">
                            <img src="<?php echo htmlspecialchars($payment_info['qr_code_url']); ?>" alt="Mã QR thanh toán" class="img-fluid border rounded" style="max-width: 300px;">
                        </div>

                        <div class="alert alert-warning">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Thông tin cực kỳ quan trọng</h5>
                            <p class="mb-1">Để được cộng tiền tự động (hoặc nhanh nhất), vui lòng đảm bảo bạn chuyển khoản với **đúng số tiền** và **đúng nội dung** dưới đây:</p>
                            <ul class="list-group">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Số tiền chính xác:</span>
                                    <strong class="text-danger fs-5 user-select-all"><?php echo format_currency($payment_info['amount']); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Nội dung chuyển khoản:</span>
                                    <strong id="paymentContent" class="text-primary fs-5 user-select-all"><?php echo htmlspecialchars($payment_info['content']); ?></strong>
                                    <button class="btn btn-outline-secondary btn-sm ms-2" onclick="copyPaymentContent()" title="Sao chép nội dung">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </li>
                            </ul>
                            <p class="mt-2 mb-0 small">Hệ thống sẽ tự động cộng tiền vào tài khoản của bạn sau vài phút khi nhận được thanh toán thành công (với cổng tự động) hoặc khi Admin xác nhận (với cổng thủ công).</p>
                        </div>

                        <div class="mt-4">
                            <a href="<?php echo base_url('user/transaction-history'); ?>" class="btn btn-primary">Tôi đã thanh toán, xem lịch sử giao dịch</a>
                            <a href="<?php echo base_url('payment/deposit'); ?>" class="btn btn-secondary">Tạo mã nạp tiền khác</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center">Không có thông tin thanh toán hợp lệ hoặc phiên đã hết hạn. Vui lòng thử lại.</div>
        <a href="<?php echo base_url('payment/deposit'); ?>" class="btn btn-primary">Quay lại trang nạp tiền</a>
    <?php endif; ?>
</div>

<script>
function copyPaymentContent() {
    var contentElement = document.getElementById('paymentContent');
    if (contentElement) {
        navigator.clipboard.writeText(contentElement.innerText).then(function() {
            // Có thể thêm thông báo nhỏ "Đã sao chép!" ở đây nếu muốn
            alert('Đã sao chép nội dung chuyển khoản: ' + contentElement.innerText);
        }, function(err) {
            console.error('Lỗi sao chép: ', err);
            alert('Không thể sao chép tự động. Vui lòng sao chép thủ công.');
        });
    }
}
</script>