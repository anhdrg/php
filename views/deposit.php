<?php
// /views/deposit.php
// Dữ liệu được truyền từ PaymentController::deposit():
// $data['gateways'] (các cổng thanh toán đang active)
// $data['errors'] (lỗi validation nếu có)
// $data['input'] (dữ liệu cũ người dùng đã nhập nếu có lỗi)

$errors = $data['errors'] ?? [];
$input = $data['input'] ?? [];
$gateways = $data['gateways'] ?? [];
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Nạp Tiền Vào Tài Khoản</h4>
                </div>
                <div class="card-body p-4">
                    <?php echo display_flash_message('global_error'); ?>
                    <p>Nhập số tiền bạn muốn nạp vào tài khoản. Số tiền sẽ được dùng để mua các sản phẩm trong shop.</p>
                    <hr>
                    <form action="<?php echo base_url('payment/generate_payment'); ?>" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="amount" class="form-label"><strong>Số tiền cần nạp (VNĐ)</strong></label>
                            <input type="number" 
                                   class="form-control form-control-lg <?php echo !empty($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                   id="amount" 
                                   name="amount" 
                                   placeholder="Ví dụ: 100000" 
                                   value="<?php echo htmlspecialchars($input['amount'] ?? ''); ?>"
                                   min="10000" step="1000" required>
                            <?php if (!empty($errors['amount'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                            <?php else: ?>
                                <div class="form-text">Số tiền nạp tối thiểu là <?php echo format_currency(10000); ?>.</div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($gateways)): ?>
                            <div class="mb-4">
                                <label class="form-label"><strong>Chọn cổng nạp tiền</strong></label>
                                <?php foreach($gateways as $key => $gateway): ?>
                                <div class="form-check border rounded p-3 mb-2">
                                    <input class="form-check-input" type="radio" name="gateway" id="gateway_<?php echo htmlspecialchars($gateway['name']); ?>" value="<?php echo htmlspecialchars($gateway['name']); ?>" <?php echo ($key == 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-flex align-items-center" for="gateway_<?php echo htmlspecialchars($gateway['name']); ?>">
                                        <?php if (!empty($gateway['logo_url'])): ?>
                                            <img src="<?php echo asset_url($gateway['logo_url']); ?>" alt="<?php echo htmlspecialchars($gateway['name']); ?>" style="height: 25px; margin-right: 10px;">
                                        <?php endif; ?>
                                        <div>
                                            <strong><?php echo htmlspecialchars($gateway['name']); ?></strong>
                                            <?php if (!empty($gateway['description'])): ?>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($gateway['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                                <?php if (!empty($errors['gateway'])): ?>
                                    <div class="text-danger small mt-2"><?php echo $errors['gateway']; ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-qrcode"></i> Tiếp Tục & Tạo Mã Nạp Tiền
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                <p class="mb-0">Hiện không có cổng nạp tiền nào được kích hoạt. Vui lòng liên hệ quản trị viên.</p>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>