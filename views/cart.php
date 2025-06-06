<?php
// /views/cart.php
// Dữ liệu được truyền từ CartController: $data['cartItems'], $data['totalAmount']
// Biến $page_title và $page_name_for_seo đã được controller đặt và có sẵn trong scope này.
?>
<div class="container mt-5 cart-page">
    <h1 class="mb-4"><?php echo htmlspecialchars($page_title ?? 'Giỏ Hàng Của Bạn'); ?></h1>

    <?php 
    echo display_flash_message('cart_success');
    echo display_flash_message('cart_error');
    echo display_flash_message('cart_info');
    echo display_flash_message('cart_updated'); // Thông báo chung nếu giỏ hàng tự động cập nhật
    // Hiển thị các thông báo cập nhật item cụ thể (nếu có, từ CartModel->getCartItems)
    if (isset($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $key => $flash_msg_item) {
            if (strpos($key, 'cart_updated_item_') === 0) {
                echo display_flash_message($key);
            }
        }
    }
    ?>

    <?php if (!empty($data['cartItems'])): ?>
        <div class="table-responsive shadow-sm rounded">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th scope="col" style="width: 10%;">Hình Ảnh</th>
                        <th scope="col" style="width: 35%;">Tên Sản Phẩm</th>
                        <th scope="col" class="text-center" style="width: 15%;">Giá</th>
                        <th scope="col" class="text-center" style="width: 15%;">Số Lượng</th>
                        <th scope="col" class="text-end" style="width: 15%;">Thành Tiền</th>
                        <th scope="col" class="text-center" style="width: 10%;">Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['cartItems'] as $item): ?>
                        <tr>
                            <td>
                                <a href="<?php echo base_url('product/show/' . $item['product_id']); ?>">
                                    <img src="<?php echo !empty($item['image_url']) ? asset_url($item['image_url']) : asset_url('images/placeholder.jpg'); ?>"
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid rounded" style="max-width: 70px; max-height: 70px; object-fit: cover;">
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo base_url('product/show/' . $item['product_id']); ?>" class="text-decoration-none text-dark fw-medium">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                                <?php if (isset($item['update_message'])): // Thông báo từ CartModel nếu số lượng tự động cập nhật ?>
                                    <p class="small text-warning mb-0"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($item['update_message']); ?></p>
                                <?php endif; ?>
                                <p class="small text-muted mb-0">Kho: <?php echo $item['stock_quantity']; ?></p>
                            </td>
                            <td class="text-center"><?php echo format_currency($item['price']); ?></td>
                            <td class="text-center">
                                <form action="<?php echo base_url('cart/update'); ?>" method="POST" class="d-inline-flex align-items-center update-quantity-form">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_quantity']; ?>" class="form-control form-control-sm text-center" style="width: 70px;" onchange="this.form.submit()">
                                    <button type="submit" class="btn btn-sm btn-link p-0 ms-1 d-none" aria-label="Cập nhật số lượng"><i class="fas fa-sync-alt"></i></button> </form>
                            </td>
                            <td class="text-end fw-medium"><?php echo format_currency($item['price'] * $item['quantity']); ?></td>
                            <td class="text-center">
                                <form action="<?php echo base_url('cart/remove'); ?>" method="POST" class="remove-item-form">
                                    <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?');" aria-label="Xóa sản phẩm">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-end border-0"><strong>Tổng cộng:</strong></td>
                        <td class="text-end fw-bold fs-5 border-0"><?php echo format_currency($data['totalAmount']); ?></td>
                        <td class="border-0"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row mt-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <a href="<?php echo base_url('product/list'); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                <form action="<?php echo base_url('cart/clear'); ?>" method="POST" class="d-inline ms-2 clear-cart-form">
                    <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Bạn có chắc chắn muốn làm trống toàn bộ giỏ hàng?');">
                        <i class="fas fa-times-circle"></i> Làm trống giỏ hàng
                    </button>
                </form>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="<?php echo base_url('checkout'); ?>" class="btn btn-primary btn-lg">
                    Tiến hành thanh toán <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-info text-center" role="alert">
            <h4 class="alert-heading">Giỏ hàng của bạn đang trống!</h4>
            <p>Hãy khám phá các sản phẩm tuyệt vời của chúng tôi và thêm chúng vào giỏ hàng.</p>
            <hr>
            <a href="<?php echo base_url('product/list'); ?>" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Bắt đầu mua sắm
            </a>
        </div>
    <?php endif; ?>
</div>