<?php
// /admin/payment-gateway-form.php
// Dữ liệu được truyền từ PaymentGatewayController::edit():
// $data['gateway'], $data['errors'], $data['form_action'], $data['form_title']
// $admin_page_title và $admin_current_page_title đã được controller đặt.

$gateway = $data['gateway'] ?? null;
$errors = $data['errors'] ?? [];

// Chuyển đổi mảng settings thành chuỗi JSON được định dạng đẹp để hiển thị trong textarea
$settingsJsonString = '';
if (!empty($gateway['settings']) && is_array($gateway['settings'])) {
    $settingsJsonString = json_encode($gateway['settings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="<?php echo base_url('admin/payment-gateway'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Danh sách Cổng Nạp
        </a>
    </div>

    <?php if ($gateway): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($data['form_title']); ?></h6>
        </div>
        <div class="card-body">
            <?php echo display_flash_message('admin_gateway_success'); ?>
            <?php echo display_flash_message('admin_gateway_error'); ?>
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['form']); ?></div>
            <?php endif; ?>
             <?php if (!empty($errors['keys'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['keys']); ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($data['form_action']); ?>" method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Tên Cổng Thanh Toán:</label>
                            <input type="text" class="form-control-plaintext" value="<?php echo htmlspecialchars($gateway['name']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả hiển thị cho người dùng</label>
                            <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($gateway['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="logo_url" class="form-label">URL Logo</label>
                            <input type="text" class="form-control <?php echo !empty($errors['logo_url']) ? 'is-invalid' : ''; ?>" id="logo_url" name="logo_url" value="<?php echo htmlspecialchars($gateway['logo_url'] ?? ''); ?>" placeholder="Ví dụ: images/logos/timo.png">
                            <?php if (!empty($gateway['logo_url'])): ?>
                                <div class="mt-2"><img src="<?php echo asset_url($gateway['logo_url']); ?>" style="max-height: 40px; background: #eee; padding: 5px;"></div>
                            <?php endif; ?>
                        </div>
                         <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php echo !empty($gateway['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Kích hoạt cổng thanh toán này</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p class="small text-muted">Lưu ý: Các thông tin dưới đây là thông tin nhạy cảm, được cung cấp bởi nhà cung cấp dịch vụ thanh toán.</p>
                        <div class="mb-3">
                            <label for="client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control" id="client_id" name="client_id" value="<?php echo htmlspecialchars($gateway['client_id'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="api_key" class="form-label">API Key</label>
                            <input type="text" class="form-control" id="api_key" name="api_key" value="<?php echo htmlspecialchars($gateway['api_key'] ?? ''); ?>">
                        </div>
                         <div class="mb-3">
                            <label for="checksum_key" class="form-label">Checksum Key / Secret Key</label>
                            <input type="text" class="form-control" id="checksum_key" name="checksum_key" value="<?php echo htmlspecialchars($gateway['checksum_key'] ?? ''); ?>">
                        </div>
                         <div class="mb-3">
                            <label for="merchant_id" class="form-label">Merchant ID (nếu có)</label>
                            <input type="text" class="form-control" id="merchant_id" name="merchant_id" value="<?php echo htmlspecialchars($gateway['merchant_id'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Cài đặt thêm (JSON)</h5>
                <p class="small text-muted">
                    Sử dụng cho các cấu hình đặc biệt của từng cổng. Ví dụ: thông tin tài khoản ngân hàng cho cổng thủ công.<br>
                    Phải là định dạng JSON hợp lệ. Ví dụ cho Timo Bank: 
                    <code>{"account_number": "0123456789", "account_name": "NGUYEN VAN A", "branch": "TP Ho Chi Minh"}</code>
                </p>
                <div class="mb-3">
                    <textarea class="form-control <?php echo !empty($errors['settings_json']) ? 'is-invalid' : ''; ?>" name="settings_json" rows="5"><?php echo htmlspecialchars($settingsJsonString); ?></textarea>
                     <?php if (!empty($errors['settings_json'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['settings_json']); ?></div><?php endif; ?>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <a href="<?php echo base_url('admin/payment-gateway'); ?>" class="btn btn-secondary me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Cấu Hình
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>
        <div class="alert alert-danger">Không tìm thấy thông tin cổng thanh toán.</div>
    <?php endif; ?>
</div>