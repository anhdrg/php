<?php
// /views/payment/deposit.php
// Dữ liệu: $data['activeGateways'], $data['errors'], $data['input']
// $page_title, $page_name_for_seo đã được controller đặt.
?>

<div class="container mt-5 deposit-page">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Nạp Tiền Vào Tài Khoản'); ?></h3>
                </div>
                <div class="card-body">
                    <?php echo display_flash_message('deposit_error'); ?>
                    <?php echo display_flash_message('deposit_info'); ?>
                    <?php echo display_flash_message('auth_error'); ?>


                    <form action="<?php echo base_url('payment/process-deposit'); ?>" method="POST" id="deposit-form">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Số tiền muốn nạp (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control <?php echo !empty($data['errors']['amount']) ? 'is-invalid' : ''; ?>" 
                                   id="amount" name="amount" 
                                   value="<?php echo htmlspecialchars($data['input']['amount'] ?? '50000'); ?>" 
                                   min="<?php echo defined('MIN_DEPOSIT_AMOUNT') ? MIN_DEPOSIT_AMOUNT : 10000; ?>" 
                                   max="<?php echo defined('MAX_DEPOSIT_AMOUNT') ? MAX_DEPOSIT_AMOUNT : 50000000; ?>" 
                                   step="1000" required>
                            <?php if (defined('MIN_DEPOSIT_AMOUNT')): ?>
                                <div class="form-text">Tối thiểu: <?php echo format_currency(MIN_DEPOSIT_AMOUNT); ?>. Tối đa: <?php echo format_currency(MAX_DEPOSIT_AMOUNT); ?>.</div>
                            <?php endif; ?>
                            <?php if (!empty($data['errors']['amount'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['amount']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Chọn cổng thanh toán <span class="text-danger">*</span></label>
                            <?php if (!empty($data['activeGateways'])): ?>
                                <?php foreach ($data['activeGateways'] as $gateway): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="payment_gateway_name" 
                                               id="gateway_<?php echo htmlspecialchars(strtolower($gateway['name'])); ?>" 
                                               value="<?php echo htmlspecialchars($gateway['name']); ?>"
                                               <?php echo (isset($data['input']['payment_gateway_name']) && $data['input']['payment_gateway_name'] == $gateway['name']) ? 'checked' : ($gateway['name'] == 'PayOS' && !isset($data['input']['payment_gateway_name']) ? 'checked' : ''); // Mặc định chọn PayOS nếu có ?>
                                               required>
                                        <label class="form-check-label" for="gateway_<?php echo htmlspecialchars(strtolower($gateway['name'])); ?>">
                                            <?php if (!empty($gateway['logo_url'])): ?>
                                                <img src="<?php echo asset_url($gateway['logo_url']); ?>" alt="<?php echo htmlspecialchars($gateway['name']); ?>" style="height: 24px; margin-right: 5px; vertical-align: middle;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($gateway['name']); ?>
                                            <?php if (!empty($gateway['description'])): ?>
                                                <small class="text-muted d-block ps-4"><?php echo htmlspecialchars($gateway['description']); ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                                <?php if (!empty($data['errors']['payment_gateway_name'])): ?>
                                    <div class="text-danger small mt-1"><?php echo $data['errors']['payment_gateway_name']; ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">Không có cổng thanh toán nào đang hoạt động.</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg" <?php echo empty($data['activeGateways']) ? 'disabled' : ''; ?>>
                                <i class="fas fa-credit-card"></i> Tiếp Tục Nạp Tiền
                            </button>
                        </div>
                    </form>
                </div>
                 <div class="card-footer text-center">
                    <p class="small text-muted mb-0">Số dư hiện tại của bạn: <strong><?php echo format_currency(getCurrentUser('balance') ?? 0); ?></strong></p>
                </div>
            </div>
        </div>
    </div>
</div>