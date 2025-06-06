<?php
// /views/transaction-history.php
// Dữ liệu: $data['transactions'], $data['currentPage'], $data['totalPages'], $data['totalTransactions']
// Biến $page_title, $page_name_for_seo đã được controller đặt.

$transaction_statuses_display = [
    TRANSACTION_STATUS_PENDING => ['text' => 'Đang chờ', 'class' => 'warning'],
    TRANSACTION_STATUS_COMPLETED => ['text' => 'Hoàn thành', 'class' => 'success'],
    TRANSACTION_STATUS_FAILED => ['text' => 'Thất bại', 'class' => 'danger'],
    TRANSACTION_STATUS_CANCELLED => ['text' => 'Đã hủy', 'class' => 'secondary'],
    TRANSACTION_STATUS_REFUNDED => ['text' => 'Đã hoàn tiền', 'class' => 'info']
];
$transaction_types_display = [
    TRANSACTION_TYPE_PURCHASE => 'Mua hàng',
    TRANSACTION_TYPE_DEPOSIT => 'Nạp tiền'
];

?>
<div class="container mt-5 transaction-history-page">
    <h1 class="mb-4"><?php echo htmlspecialchars($page_title ?? 'Lịch Sử Giao Dịch'); ?></h1>

    <?php echo display_flash_message('deposit_final_success'); ?>
    <?php echo display_flash_message('deposit_final_info'); ?>
    <?php echo display_flash_message('deposit_final_warning'); ?>
    <?php echo display_flash_message('checkout_success'); ?>


    <?php if (!empty($data['transactions'])): ?>
        <p>Tổng số giao dịch: <?php echo $data['totalTransactions']; ?></p>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col">Mã GD (#)</th>
                        <th scope="col">Ngày</th>
                        <th scope="col">Loại GD</th>
                        <th scope="col">Mô tả / Sản phẩm</th>
                        <th scope="col" class="text-end">Số tiền</th>
                        <th scope="col" class="text-center">Trạng thái</th>
                        <th scope="col">PT Thanh toán</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['transactions'] as $txn): ?>
                        <tr>
                            <td><strong><?php echo $txn['id']; ?></strong></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></td>
                            <td>
                                <?php 
                                $type_text = $transaction_types_display[$txn['transaction_type']] ?? ucfirst(str_replace('_', ' ', $txn['transaction_type']));
                                $type_badge = $txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT ? 'info' : 'primary';
                                ?>
                                <span class="badge bg-<?php echo $type_badge; ?>"><?php echo htmlspecialchars($type_text); ?></span>
                            </td>
                            <td>
                                <?php if ($txn['transaction_type'] === TRANSACTION_TYPE_PURCHASE && !empty($txn['product_name'])): ?>
                                    Mua: <a href="<?php echo base_url('product/show/' . $txn['product_id']); ?>"><?php echo htmlspecialchars($txn['product_name']); ?></a>
                                    (ID: <?php echo $txn['product_id']; ?>)
                                    <br><small class="text-muted"><?php echo htmlspecialchars($txn['description']); ?></small>
                                     <?php
                                        // Chỉ hiển thị chi tiết tài khoản nếu giao dịch đã hoàn thành
                                        // Và nếu bạn muốn người dùng xem lại thông tin acc đã mua ở đây.
                                        // Cần lấy `account_details` từ bảng `products` (TransactionModel::getUserTransactions cần JOIN và SELECT thêm)
                                        // Hoặc bạn có thể tạo một trang chi tiết giao dịch riêng để hiển thị.
                                        // if ($txn['status'] === TRANSACTION_STATUS_COMPLETED && isset($txn['account_details_purchased'])) {
                                        //     echo "<br><small class='text-success fw-bold'>Thông tin tài khoản:</small><pre class='small bg-light p-1 rounded'>" . htmlspecialchars($txn['account_details_purchased']) . "</pre>";
                                        // }
                                    ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($txn['description'] ?? 'N/A'); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-medium <?php echo $txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT ? 'text-success' : 'text-danger'; ?>">
                                <?php echo ($txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT ? '+' : '-') . format_currency($txn['amount']); ?>
                            </td>
                            <td class="text-center">
                                <?php
                                $status_info = $transaction_statuses_display[$txn['status']] ?? ['text' => ucfirst(str_replace('_', ' ', $txn['status'])), 'class' => 'secondary'];
                                ?>
                                <span class="badge bg-<?php echo $status_info['class']; ?>"><?php echo htmlspecialchars($status_info['text']); ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($txn['payment_method'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($data['totalPages'] > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    if ($data['currentPage'] > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($data['currentPage'] - 1) . '">Trước</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Trước</span></li>';
                    }
                    // ... (Logic phân trang đầy đủ như các trang khác) ...
                    $num_links = 2; $start = max(1, $data['currentPage'] - $num_links); $end = min($data['totalPages'], $data['currentPage'] + $num_links);
                    if ($start > 1) { echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>'; if ($start > 2) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } }
                    for ($i = $start; $i <= $end; $i++) { echo '<li class="page-item ' . ($i == $data['currentPage'] ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>'; }
                    if ($end < $data['totalPages']) { if ($end < $data['totalPages'] - 1) { echo '<li class="page-item disabled"><span class="page-link">...</span></li>'; } echo '<li class="page-item"><a class="page-link" href="?page=' . $data['totalPages'] . '">' . $data['totalPages'] . '</a></li>'; }
                    if ($data['currentPage'] < $data['totalPages']) {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . ($data['currentPage'] + 1) . '">Sau</a></li>';
                    } else {
                        echo '<li class="page-item disabled"><span class="page-link">Sau</span></li>';
                    }
                    ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <h4 class="alert-heading">Bạn chưa có giao dịch nào!</h4>
            <?php if (isLoggedIn()): ?>
            <p>Hãy <a href="<?php echo base_url('payment/deposit'); ?>">nạp tiền</a> để bắt đầu hoặc <a href="<?php echo base_url('product/list'); ?>">xem sản phẩm</a> của chúng tôi.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
