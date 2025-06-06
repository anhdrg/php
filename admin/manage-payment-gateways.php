<?php
// /admin/manage-payment-gateways.php
// Dữ liệu được truyền từ PaymentGatewayController::index():
// $data['gateways']
// $admin_page_title và $admin_current_page_title đã được controller (thông qua AdminBaseController) đặt.
?>

<div class="container-fluid">
    <?php echo display_flash_message('admin_gateway_success'); ?>
    <?php echo display_flash_message('admin_gateway_error'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Quản Lý Cổng Nạp Tiền / Thanh Toán</h6>
            </div>
        <div class="card-body">
            <?php if (!empty($data['gateways'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableGateways" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên Cổng</th>
                                <th>Mô tả</th>
                                <th class="text-center">Logo</th>
                                <th class="text-center">Trạng thái</th>
                                <th>Cập nhật lần cuối</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['gateways'] as $gateway): ?>
                                <tr>
                                    <td><?php echo $gateway['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($gateway['name']); ?></strong></td>
                                    <td><?php echo nl2br(htmlspecialchars($gateway['description'] ?? 'N/A')); ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($gateway['logo_url'])): ?>
                                            <img src="<?php echo asset_url($gateway['logo_url']); ?>" alt="<?php echo htmlspecialchars($gateway['name']); ?>" style="max-height: 30px; max-width: 100px; background: #f8f9fa; padding: 2px; border:1px solid #ddd;">
                                        <?php else: ?>
                                            <em>Chưa có</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($gateway['is_active']): ?>
                                            <span class="badge bg-success">Đang hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($gateway['updated_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('admin/payment-gateway/edit/' . $gateway['id']); // ĐÃ SỬA ĐÚNG LINK ?>" class="btn btn-warning btn-sm" title="Sửa cấu hình">
                                            <i class="fas fa-edit"></i> Cấu hình
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">Hiện không có cổng thanh toán nào được cấu hình trong hệ thống.</div>
            <?php endif; ?>
        </div>
    </div>
</div>