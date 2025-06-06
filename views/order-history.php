<?php
// /views/order-history.php
// Dữ liệu: $data['orders'], $data['currentPage'], $data['totalPages'], $data['totalOrders']
// Biến $page_title, $page_name_for_seo đã được controller đặt.

$transaction_statuses_display = [ // Có thể không cần nếu chỉ hiển thị đơn COMPLETED
    TRANSACTION_STATUS_PENDING => ['text' => 'Đang chờ', 'class' => 'warning'],
    TRANSACTION_STATUS_COMPLETED => ['text' => 'Hoàn thành', 'class' => 'success'],
    TRANSACTION_STATUS_FAILED => ['text' => 'Thất bại', 'class' => 'danger'],
    TRANSACTION_STATUS_CANCELLED => ['text' => 'Đã hủy', 'class' => 'secondary'],
    TRANSACTION_STATUS_REFUNDED => ['text' => 'Đã hoàn tiền', 'class' => 'info']
];
?>
<div class="container mt-5 order-history-page">
    <h1 class="mb-4"><?php echo htmlspecialchars($page_title ?? 'Đơn Hàng Đã Mua'); ?></h1>

    <?php echo display_flash_message('checkout_success'); ?>
    <?php echo display_flash_message('global_error'); // Cho các lỗi chung ?>


    <?php if (!empty($data['orders'])): ?>
        <p>Bạn có tổng cộng <?php echo $data['totalOrders']; ?> đơn hàng đã mua thành công.</p>
        
        <?php foreach ($data['orders'] as $order): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap">
                    <span class="me-3">Đơn hàng #<?php echo htmlspecialchars($order['transaction_id'] ?? 'N/A'); ?> - Ngày: <?php echo isset($order['purchase_date']) ? date('d/m/Y H:i', strtotime($order['purchase_date'])) : 'N/A'; ?></span>
                    <span class="fw-bold text-danger"><?php echo format_currency($order['amount'] ?? 0); ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-2 text-center mb-3 mb-md-0">
                            <img src="<?php echo !empty($order['product_type_image']) ? asset_url($order['product_type_image']) : asset_url('images/placeholder.jpg'); ?>" 
                                 alt="<?php echo htmlspecialchars($order['product_type_name'] ?? 'Sản phẩm'); ?>" 
                                 class="img-fluid rounded" style="max-height: 100px; max-width: 100px; object-fit:cover;">
                        </div>
                        <div class="col-md-10">
                            <h5 class="card-title"><?php echo htmlspecialchars($order['product_type_name'] ?? 'Không có tên'); ?></h5>
                            
                            <?php if (!empty($order['game_code_data'])): ?>
                                <p class="card-text mb-1">
                                    <strong>Mã Recovery / Thông tin tài khoản:</strong>
                                    <button class="btn btn-sm btn-outline-secondary ms-2 btn-copy-code" 
                                            data-code="<?php echo htmlspecialchars($order['game_code_data'] ?? ''); ?>" 
                                            title="Sao chép mã">
                                        <i class="fas fa-copy"></i> Sao chép
                                    </button>
                                </p>
                                <div class="p-2 bg-light border rounded code-display-area mb-2" style="white-space: pre-wrap; word-break: break-all; font-family: monospace; max-height: 150px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($order['game_code_data'] ?? '')); // Dòng 38 (hoặc gần đó) ?>
                                </div>
                                <?php if (!empty($order['game_code_notes'])): ?>
                                <p class="card-text mb-1"><small class="text-muted"><em>Ghi chú thêm: <?php echo htmlspecialchars($order['game_code_notes']); ?></em></small></p>
                                <?php endif; ?>
                                <small class="text-muted">ID Mã Game nội bộ: #<?php echo htmlspecialchars($order['recovery_code_internal_id'] ?? 'N/A'); ?></small>
                            <?php elseif (isset($order['linked_recovery_code_id']) && $order['linked_recovery_code_id'] === null): ?>
                                <p class="text-warning"><em>Thông tin mã game cho đơn hàng này chưa được cập nhật hoặc có lỗi. Vui lòng liên hệ hỗ trợ.</em></p>
                            <?php else: ?>
                                <p class="text-muted"><em>Không có thông tin mã game cho đơn hàng này.</em></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (($data['totalPages'] ?? 0) > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    if (($data['currentPage'] ?? 1) > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . (($data['currentPage'] ?? 1) - 1) . '">Trước</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Trước</span></li>';
                    }
                    $num_links = 2; $start = max(1, ($data['currentPage'] ?? 1) - $num_links); $end = min(($data['totalPages'] ?? 1), ($data['currentPage'] ?? 1) + $num_links);
                    if ($start > 1) { echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>'; if ($start > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
                    for ($i = $start; $i <= $end; $i++) { echo '<li class="page-item ' . ($i == ($data['currentPage'] ?? 1) ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>'; }
                    if ($end < ($data['totalPages'] ?? 1)) { if ($end < ($data['totalPages'] ?? 1) - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } echo '<li class="page-item"><a class="page-link" href="?page=' . ($data['totalPages'] ?? 1) . '">' . ($data['totalPages'] ?? 1) . '</a></li>'; }
                    if (($data['currentPage'] ?? 1) < ($data['totalPages'] ?? 1)) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . (($data['currentPage'] ?? 1) + 1) . '">Sau</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Sau</span></li>';
                    }
                    ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <h4 class="alert-heading">Bạn chưa mua đơn hàng nào!</h4>
            <p>Hãy <a href="<?php echo base_url('product/list'); ?>">xem các sản phẩm</a> và bắt đầu mua sắm.</p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const copyButtons = document.querySelectorAll('.btn-copy-code');
    copyButtons.forEach(button => {
        button.addEventListener('click', function () {
            const codeToCopy = this.dataset.code;
            if (codeToCopy) { // Chỉ sao chép nếu có dữ liệu
                navigator.clipboard.writeText(codeToCopy).then(() => {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i> Đã chép!';
                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-success');
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.classList.remove('btn-success');
                        this.classList.add('btn-outline-secondary');
                    }, 2000);
                }).catch(err => {
                    console.error('Lỗi khi sao chép: ', err);
                    // Fallback cho trường hợp lỗi (ví dụ trình duyệt không hỗ trợ hoặc không cho phép)
                    try {
                        const textArea = document.createElement('textarea');
                        textArea.value = codeToCopy;
                        document.body.appendChild(textArea);
                        textArea.select();
                        document.execCommand('copy');
                        document.body.removeChild(textArea);
                        // Thông báo thành công cho fallback
                        const originalText = this.innerHTML;
                        this.innerHTML = '<i class="fas fa-check"></i> Đã chép!';
                        this.classList.remove('btn-outline-secondary');
                        this.classList.add('btn-success');
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.classList.remove('btn-success');
                            this.classList.add('btn-outline-secondary');
                        }, 2000);
                    } catch (fallbackErr) {
                        alert('Không thể sao chép tự động. Vui lòng sao chép thủ công.');
                    }
                });
            } else {
                alert('Không có nội dung để sao chép.');
            }
        });
    });
});
</script>