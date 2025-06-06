<?php
// /admin/view-transaction.php
// Dữ liệu được truyền từ TransactionController::view($id): $data['transaction']
// $admin_page_title và $admin_current_page_title đã được controller đặt.
$txn = $data['transaction'] ?? null;

// Mảng helper để hiển thị tên thân thiện cho các giá trị enum/varchar
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
$payment_methods_display = [
    PAYMENT_METHOD_BALANCE => 'Số dư',
    PAYMENT_METHOD_PAYOS => 'PayOS',
    PAYMENT_METHOD_SEPAY => 'SEPay',
    PAYMENT_METHOD_ADMIN => 'Admin Điều Chỉnh'
];
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="<?php echo base_url('admin/transaction'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Danh sách Giao dịch
        </a>
    </div>

    <?php if ($txn): ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Thông Tin Giao Dịch #<?php echo htmlspecialchars($txn['id']); ?></h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 35%;">ID Giao Dịch</th>
                                <td><?php echo htmlspecialchars($txn['id']); ?></td>
                            </tr>
                            <tr>
                                <th>Loại Giao Dịch</th>
                                <td>
                                    <?php 
                                    $type_text = $transaction_types_display[$txn['transaction_type']] ?? ucfirst(str_replace('_', ' ', $txn['transaction_type']));
                                    $type_badge_class = ($txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT) ? 'info text-dark' : 'primary';
                                    ?>
                                    <span class="badge bg-<?php echo $type_badge_class; ?>"><?php echo htmlspecialchars($type_text); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Trạng thái</th>
                                <td>
                                    <?php 
                                    $status_info = $transaction_statuses_display[$txn['status']] ?? ['text' => ucfirst($txn['status']), 'class' => 'dark'];
                                    ?>
                                    <span class="badge bg-<?php echo htmlspecialchars($status_info['class']); ?>"><?php echo htmlspecialchars($status_info['text']); ?></span>
                                </td>
                            </tr>
                            <tr>
                                <th>Số tiền</th>
                                <td class="fw-bold <?php echo $txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo ($txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT ? '+' : '-') . format_currency($txn['amount']); ?>
                                </td>
                            </tr>
                            <tr>
                                <th>Phương thức Thanh toán</th>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?php echo htmlspecialchars($payment_methods_display[$txn['payment_method']] ?? $txn['payment_method'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Mã GD Cổng Thanh toán</th>
                                <td><code><?php echo htmlspecialchars($txn['payment_gateway_txn_id'] ?? 'N/A'); ?></code></td>
                            </tr>
                             <tr>
                                <th>Mô tả</th>
                                <td><?php echo nl2br(htmlspecialchars($txn['description'] ?? '')); ?></td>
                            </tr>
                             <tr>
                                <th>Ngày tạo</th>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($txn['created_at'])); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Thông Tin Người Dùng</h6></div>
                <div class="card-body">
                    <?php if (isset($txn['user'])): $user = $txn['user']; ?>
                        <p><strong>ID:</strong> <?php echo $user['id']; ?></p>
                        <p><strong>Username:</strong> <a href="<?php echo base_url('admin/user/edit/' . $user['id']); ?>"><?php echo htmlspecialchars($user['username']); ?></a></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></p>
                    <?php else: ?>
                        <p class="text-muted">Không có thông tin người dùng.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($txn['transaction_type'] === TRANSACTION_TYPE_PURCHASE): ?>
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Thông Tin Sản Phẩm Đã Bán</h6></div>
                    <div class="card-body">
                         <?php if (isset($txn['product_type'])): $product_type = $txn['product_type']; ?>
                             <p><strong>Loại Sản phẩm:</strong> <a href="<?php echo base_url('admin/product/edit/' . $product_type['id']); ?>"><?php echo htmlspecialchars($product_type['name']); ?></a> (ID: <?php echo $product_type['id']; ?>)</p>
                         <?php else: ?>
                             <p class="text-muted">Không có thông tin loại sản phẩm.</p>
                         <?php endif; ?>

                         <hr>
                         <p class="mb-2"><strong>Mã Game / Tài Khoản đã giao:</strong></p>
                         <?php if (isset($txn['game_code_sold'])): $game_code = $txn['game_code_sold']; ?>
                            <p class="small mb-1"><strong>ID Mã Game:</strong> <a href="<?php echo base_url('admin/game-code/edit/' . $game_code['id']); ?>">#<?php echo $game_code['id']; ?></a></p>
                            <p class="small mb-1"><strong>Nội dung:</strong></p>
                            <pre class="bg-light p-2 border rounded" style="white-space: pre-wrap; word-break: break-all;"><code><?php echo htmlspecialchars($game_code['code_value']); ?></code></pre>
                            <?php if (!empty($game_code['notes'])): ?>
                                 <p class="small mb-1"><strong>Ghi chú của Admin:</strong> <?php echo htmlspecialchars($game_code['notes']); ?></p>
                            <?php endif; ?>
                         <?php else: ?>
                             <p class="text-danger"><em>Lỗi: Không tìm thấy thông tin mã game được liên kết với giao dịch này! (recovery_code_id: <?php echo htmlspecialchars($txn['recovery_code_id'] ?? 'NULL'); ?>)</em></p>
                         <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">Không tìm thấy giao dịch.</div>
    <?php endif; ?>
</div>