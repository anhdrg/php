<?php
// /admin/manage-transactions.php
// Dữ liệu được truyền từ TransactionController::index()
$filters = $data['filters'] ?? [];
$orderBy = $data['orderBy'] ?? 't.created_at';
$orderDir = $data['orderDir'] ?? 'DESC';

// Mảng helper để hiển thị tên thân thiện
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
    <?php echo display_flash_message('admin_transaction_success'); ?>
    <?php echo display_flash_message('admin_transaction_error'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Giao Dịch (Tổng: <?php echo $data['totalTransactions'] ?? 0; ?>)</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('admin/transaction'); ?>" method="GET" class="mb-4 p-3 border rounded bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="search_term" class="form-label form-label-sm">Tìm kiếm chung</label>
                        <input type="text" name="search_term" id="search_term" class="form-control form-control-sm" placeholder="ID, User, Sản phẩm..." value="<?php echo htmlspecialchars($filters['search_term'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="status" class="form-label form-label-sm">Trạng thái</label>
                        <select name="status" id="status" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($transaction_statuses_display as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($filters['status']) && $filters['status'] == $key) ? 'selected' : ''; ?>><?php echo $value['text']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="transaction_type" class="form-label form-label-sm">Loại GD</label>
                        <select name="transaction_type" id="transaction_type" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                             <?php foreach ($transaction_types_display as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($filters['transaction_type']) && $filters['transaction_type'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="payment_method" class="form-label form-label-sm">Phương thức TT</label>
                        <select name="payment_method" id="payment_method" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                             <?php foreach ($payment_methods_display as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($filters['payment_method']) && $filters['payment_method'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-info btn-sm w-100">Lọc</button>
                        <?php if (!empty($filters)): ?>
                        <a href="<?php echo base_url('admin/transaction'); ?>" class="btn btn-outline-secondary btn-sm ms-2" title="Xóa bộ lọc"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (!empty($data['transactions'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm" id="dataTableTransactions" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <?php
                                if (!function_exists('create_sort_link_for_txn')) {
                                    function create_sort_link_for_txn($columnName, $displayName, $currentOrderBy, $currentOrderDir, $currentFilters) {
                                        $newDir = ($currentOrderBy == $columnName && $currentOrderDir == 'ASC') ? 'DESC' : 'ASC';
                                        $arrow = $currentOrderBy == $columnName ? ($newDir == 'DESC' ? ' <i class="fas fa-sort-up"></i>' : ' <i class="fas fa-sort-down"></i>') : ' <i class="fas fa-sort text-muted"></i>';
                                        $queryParams = array_merge($currentFilters, ['sort' => $columnName, 'dir' => $newDir]);
                                        return '<a href="?' . http_build_query($queryParams) . '">' . $displayName . $arrow . '</a>';
                                    }
                                }
                                ?>
                                <th><?php echo create_sort_link_for_txn('t.id', 'ID', $orderBy, $orderDir, $filters); ?></th>
                                <th><?php echo create_sort_link_for_txn('u.username', 'Người dùng', $orderBy, $orderDir, $filters); ?></th>
                                <th>Loại GD</th>
                                <th>Sản phẩm</th>
                                <th class="text-end"><?php echo create_sort_link_for_txn('t.amount', 'Số tiền', $orderBy, $orderDir, $filters); ?></th>
                                <th>Phương thức TT</th>
                                <th class="text-center"><?php echo create_sort_link_for_txn('t.status', 'Trạng thái', $orderBy, $orderDir, $filters); ?></th>
                                <th class="text-center"><?php echo create_sort_link_for_txn('t.created_at', 'Ngày tạo', $orderBy, $orderDir, $filters); ?></th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['transactions'] as $txn): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($txn['id']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['user_username'] ?? 'N/A'); ?> (ID: <?php echo htmlspecialchars($txn['user_id']); ?>)</td>
                                    <td>
                                        <?php 
                                        $type_text = $transaction_types_display[$txn['transaction_type']] ?? ucfirst(str_replace('_', ' ', $txn['transaction_type']));
                                        $type_badge_class = ($txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT) ? 'info text-dark' : 'primary';
                                        ?>
                                        <span class="badge bg-<?php echo $type_badge_class; ?>"><?php echo htmlspecialchars($type_text); ?></span>
                                    </td>
                                    <td><?php echo !empty($txn['product_type_name']) ? htmlspecialchars($txn['product_type_name']) . ' (ID: '.($txn['product_id'] ?? 'N/A').')' : 'N/A'; ?></td>
                                    <?php
                                    $amount_class = ''; $amount_prefix = '';
                                    if ($txn['transaction_type'] === TRANSACTION_TYPE_DEPOSIT) {
                                        $amount_class = 'text-success'; $amount_prefix = '+ ';
                                    } elseif ($txn['transaction_type'] === TRANSACTION_TYPE_PURCHASE) {
                                        $amount_class = 'text-danger'; $amount_prefix = '- ';
                                    }
                                    ?>
                                    <td class="text-end fw-bold <?php echo $amount_class; ?>"><?php echo $amount_prefix . format_currency($txn['amount']); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo htmlspecialchars($payment_methods_display[$txn['payment_method']] ?? $txn['payment_method'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php $status_info = $transaction_statuses_display[$txn['status']] ?? ['text' => ucfirst($txn['status']), 'class' => 'secondary']; ?>
                                        <span class="badge bg-<?php echo htmlspecialchars($status_info['class']); ?>"><?php echo htmlspecialchars($status_info['text']); ?></span>
                                    </td>
                                    <td class="text-center"><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('admin/transaction/view/' . $txn['id']); ?>" class="btn btn-info btn-sm" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                                        <?php if ($txn['status'] === TRANSACTION_STATUS_PENDING): ?>
                                            <form action="<?php echo base_url('admin/transaction/approve'); ?>" method="POST" class="d-inline ms-1 approve-txn-form">
                                                <input type="hidden" name="transaction_id" value="<?php echo $txn['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Duyệt giao dịch này" onclick="return confirm('Duyệt giao dịch #<?php echo $txn['id']; ?> và cộng tiền cho người dùng?');">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (($data['totalPages'] ?? 0) > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php
                            $currentUrlParams = $filters; 
                            if (!empty($orderBy)) $currentUrlParams['sort'] = $orderBy;
                            if (!empty($orderDir)) $currentUrlParams['dir'] = $orderDir;
                            if (($data['currentPage'] ?? 1) > 1): $currentUrlParams['page'] = ($data['currentPage'] ?? 1) - 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Trước</a></li>
                            <?php else: ?><li class="page-item disabled"><span class="page-link">Trước</span></li><?php endif; 
                            
                            $num_links = 2; $start = max(1, ($data['currentPage'] ?? 1) - $num_links); $end = min(($data['totalPages'] ?? 1), ($data['currentPage'] ?? 1) + $num_links);
                            if ($start > 1): $currentUrlParams['page'] = 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
                            for ($i = $start; $i <= $end; $i++): $currentUrlParams['page'] = $i; ?> <li class="page-item <?php echo ($i == ($data['currentPage'] ?? 1) ? 'active' : ''); ?>"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo $i; ?></a></li><?php endfor;
                            if ($end < ($data['totalPages'] ?? 1)): if ($end < ($data['totalPages'] ?? 1) - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; $currentUrlParams['page'] = ($data['totalPages'] ?? 1); ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo ($data['totalPages'] ?? 1); ?></a></li><?php endif; ?>
                            <?php if (($data['currentPage'] ?? 1) < ($data['totalPages'] ?? 1)): $currentUrlParams['page'] = ($data['currentPage'] ?? 1) + 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Sau</a></li>
                            <?php else: ?><li class="page-item disabled"><span class="page-link">Sau</span></li><?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center">Không tìm thấy giao dịch nào.</div>
            <?php endif; ?>
        </div>
    </div>
</div>