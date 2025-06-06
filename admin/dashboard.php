<?php
// /admin/dashboard.php
// Dữ liệu được truyền từ AdminController: $data['totalUsers'], $data['totalProducts'], etc.
// $admin_page_title và $admin_current_page_title đã được controller đặt.
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tổng Số Người Dùng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['totalUsers'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tổng Sản Phẩm (Còn bán)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['totalAvailableProducts'] ?? 0; ?> / <?php echo $data['totalProducts'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tổng Số Giao Dịch</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['totalTransactions'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tổng Doanh Thu (Hoàn thành)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_currency($data['totalRevenue'] ?? 0); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Giao dịch gần đây</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['recentTransactions'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người dùng</th>
                                        <th>Số tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($data['recentTransactions'] as $txn): ?>
                                    <tr>
                                        <td><a href="<?php echo base_url('admin/view-transaction/' . $txn['id']); ?>">#<?php echo $txn['id']; ?></a></td>
                                        <td>
                                            <?php if (isset($txn['user_username'])): ?>
                                                <a href="<?php echo base_url('admin/user-edit/' . $txn['user_id']); ?>"><?php echo htmlspecialchars($txn['user_username']); ?></a>
                                            <?php else: ?>
                                                <em>Không rõ</em>
                                            <?php endif; ?>
                                        </td>
                                        <td class="<?php echo ($txn['transaction_type'] ?? '') === TRANSACTION_TYPE_DEPOSIT ? 'text-success' : (($txn['transaction_type'] ?? '') === TRANSACTION_TYPE_PURCHASE ? 'text-danger' : ''); ?>">
                                            <?php echo ($txn['transaction_type'] ?? '') === TRANSACTION_TYPE_DEPOSIT ? '+' : '-'; ?>
                                            <?php echo format_currency($txn['amount']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary'; $status_text = ucfirst(str_replace('_', ' ', $txn['status']));
                                            if ($txn['status'] === TRANSACTION_STATUS_COMPLETED) $status_class = 'success';
                                            else if ($txn['status'] === TRANSACTION_STATUS_PENDING) $status_class = 'warning';
                                            else if ($txn['status'] === TRANSACTION_STATUS_FAILED || $txn['status'] === TRANSACTION_STATUS_CANCELLED) $status_class = 'danger';
                                            else if ($txn['status'] === TRANSACTION_STATUS_REFUNDED) $status_class = 'info';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($txn['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                         <div class="text-end mt-2">
                            <a href="<?php echo base_url('admin/manage-transactions'); ?>">Xem tất cả giao dịch &rarr;</a>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Chưa có giao dịch nào gần đây.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Phân loại Sản phẩm (Ví dụ)</h6>
                </div>
                <div class="card-body text-center">
                    <p class="text-muted">Khu vực này có thể hiển thị biểu đồ.</p>
                    <i class="fas fa-chart-pie fa-4x text-gray-300 mt-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>