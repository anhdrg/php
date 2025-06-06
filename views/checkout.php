<?php
// /views/checkout.php
// Dữ liệu: $data['cartItems'], $data['totalAmount'], $data['currentUser'], $data['paymentGateways']
// Biến $page_title và $page_name_for_seo đã được controller đặt và có sẵn trong scope này.
?>

<div class="container mt-5 checkout-page">
    <h1 class="mb-4"><?php echo htmlspecialchars($page_title ?? 'Thanh Toán Đơn Hàng'); ?></h1>

    <?php 
    // Hiển thị các flash message chung
    echo display_flash_message('global_error');
    echo display_flash_message('global_success');
    echo display_flash_message('global_info');
    
    // Hiển thị các flash message cụ thể cho trang checkout
    echo display_flash_message('checkout_error');
    echo display_flash_message('checkout_success'); // Thường không dùng ở đây, mà ở trang thank-you
    echo display_flash_message('checkout_info');
    echo display_flash_message('checkout_login_required');
    echo display_flash_message('cart_empty'); // Nếu giỏ hàng trống và bị redirect về
    ?>


    <?php if (isLoggedIn() && !empty($data['cartItems'])): ?>
        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">Thông Tin Đơn Hàng (<?php echo count($data['cartItems']); ?> sản phẩm)</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($data['cartItems'] as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="my-0"><?php echo htmlspecialchars($item['name']); ?> (x<?php echo $item['quantity']; ?>)</h6>
                                        <small class="text-muted">Giá: <?php echo format_currency($item['price']); ?></small>
                                    </div>
                                    <span class="text-muted"><?php echo format_currency($item['price'] * $item['quantity']); ?></span>
                                </li>
                            <?php endforeach; ?>
                            <li class="list-group-item d-flex justify-content-between bg-light">
                                <strong class="fs-5">Tổng cộng:</strong>
                                <strong class="fs-5 text-danger"><?php echo format_currency($data['totalAmount']); ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Chọn Phương Thức Thanh Toán</h4>
                    </div>
                    <div class="card-body">
                        <form action="<?php echo base_url('checkout/process'); ?>" method="POST" id="checkout-form">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="payment_balance" value="<?php echo PAYMENT_METHOD_BALANCE; ?>" required 
                                           <?php if($data['currentUser']['balance'] < $data['totalAmount']) echo 'disabled'; ?>
                                           <?php if($data['currentUser']['balance'] >= $data['totalAmount']) echo 'checked'; // Mặc định chọn nếu đủ tiền ?> >
                                    <label class="form-check-label fw-medium" for="payment_balance">
                                        Thanh toán bằng số dư tài khoản
                                        <span class="d-block small text-muted">
                                            Số dư hiện tại: <?php echo format_currency($data['currentUser']['balance']); ?>
                                            <?php if($data['currentUser']['balance'] < $data['totalAmount']): ?>
                                                <span class="text-danger">(Không đủ số dư - Cần <a href="<?php echo base_url('payment/deposit'); ?>">nạp thêm</a>)</span>
                                            <?php endif; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <?php /* <hr>
                            <p class="text-muted">Hoặc chọn cổng thanh toán khác (nếu bạn muốn tích hợp thanh toán trực tiếp đơn hàng):</p>
                            <?php if (!empty($data['paymentGateways'])): ?>
                                <?php foreach ($data['paymentGateways'] as $gateway): ?>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="radio" name="payment_method" 
                                               id="payment_<?php echo htmlspecialchars(strtolower($gateway['name'])); ?>" 
                                               value="<?php echo htmlspecialchars($gateway['name']); ?>" required
                                               <?php if($data['currentUser']['balance'] < $data['totalAmount'] && $gateway['name'] == 'PayOS') echo 'checked'; // Ví dụ: mặc định PayOS nếu không đủ số dư ?> >
                                        <label class="form-check-label fw-medium" for="payment_<?php echo htmlspecialchars(strtolower($gateway['name'])); ?>">
                                            <?php if (!empty($gateway['logo_url'])): ?>
                                                <img src="<?php echo asset_url($gateway['logo_url']); ?>" alt="<?php echo htmlspecialchars($gateway['name']); ?>" style="height: 24px; margin-right: 5px;">
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($gateway['name']); ?>
                                            <?php if (!empty($gateway['description'])): ?>
                                                <span class="d-block small text-muted"><?php echo htmlspecialchars($gateway['description']); ?></span>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Không có cổng thanh toán trực tuyến nào được cấu hình cho việc mua hàng trực tiếp.</p>
                            <?php endif; ?>
                            */ ?>

                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-success btn-lg" id="btn-place-order" <?php if($data['currentUser']['balance'] < $data['totalAmount']) echo 'disabled'; ?>>
                                    <i class="fas fa-shield-alt"></i> Thanh Toán Đơn Hàng
                                </button>
                                <?php if($data['currentUser']['balance'] < $data['totalAmount']): ?>
                                    <a href="<?php echo base_url('payment/deposit'); ?>" class="btn btn-info btn-lg mt-2">
                                        <i class="fas fa-wallet"></i> Nạp Thêm Tiền Vào Ví
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="<?php echo base_url('cart'); ?>"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>
                </div>
            </div>
        </div>
    <?php elseif (!isLoggedIn()): ?>
        <div class="alert alert-warning text-center">
            <p>Bạn cần đăng nhập để tiến hành thanh toán.</p>
            <a href="<?php echo base_url('user/login?redirect=' . urlencode(base_url('checkout'))); ?>" class="btn btn-primary">Đăng Nhập Ngay</a>
        </div>
    <?php elseif (empty($data['cartItems'])): ?>
        <div class="alert alert-info text-center">
             <p>Giỏ hàng của bạn đang trống. Không thể tiến hành thanh toán.</p>
             <a href="<?php echo base_url('product/list'); ?>" class="btn btn-primary">Tiếp tục mua sắm</a>
        </div>
    <?php endif; ?>
</div>