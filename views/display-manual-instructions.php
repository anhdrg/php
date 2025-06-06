<?php
// /views/display-manual-instructions.php
$payment_info = $data['payment_info'] ?? null;
$bank_details = $payment_info['bank_details'] ?? [];
?>
<div class="container mt-5">
    <?php if ($payment_info): ?>
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card text-center shadow">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Hướng Dẫn Nạp Tiền Thủ Công</h4>
                    </div>
                    <div class="card-body p-4">
                        <p>Vui lòng chuyển khoản chính xác các thông tin dưới đây qua app ngân hàng của bạn.</p>
                        
                        <div class="alert alert-primary text-start">
                            <h5 class="alert-heading">Thông tin tài khoản nhận</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><strong>Ngân hàng:</strong> <?php echo htmlspecialchars($payment_info['gateway_name']); ?></li>
                                <li class="list-group-item"><strong>Số tài khoản:</strong> <strong class="fs-5 text-primary user-select-all"><?php echo htmlspecialchars($bank_details['account_number'] ?? 'N/A'); ?></strong></li>
                                <li class="list-group-item"><strong>Chủ tài khoản:</strong> <?php echo htmlspecialchars($bank_details['account_name'] ?? 'N/A'); ?></li>
                                <?php if (!empty($bank_details['branch'])): ?>
                                <li class="list-group-item"><strong>Chi nhánh:</strong> <?php echo htmlspecialchars($bank_details['branch']); ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>

                        <div class="alert alert-warning mt-3 text-start">
                            <h5 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Thông tin giao dịch</h5>
                             <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Số tiền chính xác:
                                    <strong class="text-danger fs-5"><?php echo format_currency($payment_info['amount']); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Nội dung chuyển khoản:
                                    <strong id="paymentContentManual" class="text-primary fs-5 user-select-all"><?php echo htmlspecialchars($payment_info['content']); ?></strong>
                                    <button class="btn btn-outline-secondary btn-sm ms-2" onclick="copyManualPaymentContent()" title="Sao chép nội dung">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </li>
                            </ul>
                            <p class="mt-2 mb-0 small text-danger fw-bold">Vui lòng nhập ĐÚNG NỘI DUNG chuyển khoản để giao dịch được xử lý nhanh chóng!</p>
                        </div>

                        <p class="mt-4">Sau khi chuyển khoản, giao dịch của bạn sẽ ở trạng thái "Đang chờ". Admin sẽ kiểm tra và duyệt giao dịch trong thời gian sớm nhất.</p>

                        <div class="mt-4">
                            <a href="<?php echo base_url('user/transaction-history'); ?>" class="btn btn-primary">Tôi đã thanh toán, xem lịch sử giao dịch</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center">Không có thông tin thanh toán hợp lệ. Vui lòng thử lại.</div>
        <a href="<?php echo base_url('payment/deposit'); ?>" class="btn btn-primary">Quay lại trang nạp tiền</a>
    <?php endif; ?>
</div>

<script>
function copyManualPaymentContent() {
    var contentElement = document.getElementById('paymentContentManual');
    if (contentElement) {
        navigator.clipboard.writeText(contentElement.innerText).then(function() {
            alert('Đã sao chép nội dung chuyển khoản: ' + contentElement.innerText);
        }, function(err) {
            alert('Không thể sao chép tự động. Vui lòng sao chép thủ công.');
        });
    }
}
</script>