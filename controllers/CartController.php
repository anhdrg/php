<?php
// /controllers/CartController.php

class CartController {
    private $cartModel;
    private $productModel;

    public function __construct() {
        if (class_exists('CartModel')) {
            $this->cartModel = new CartModel();
        } else {
            write_log("FATAL ERROR: Class CartModel không tìm thấy trong CartController.", "ERROR");
            die('Lỗi hệ thống: CartModel không khả dụng.');
        }

        if (class_exists('ProductModel')) {
            $this->productModel = new ProductModel();
        } else {
            write_log("FATAL ERROR: Class ProductModel không tìm thấy trong CartController.", "ERROR");
            die('Lỗi hệ thống: ProductModel không khả dụng.');
        }
    }

    /**
     * Hiển thị trang giỏ hàng.
     * URL: /cart hoặc /cart/index
     */
    public function index() {
        // Mặc dù luồng chính là "Mua Ngay", trang giỏ hàng vẫn có thể tồn tại
        // để người dùng xem các sản phẩm họ đã từng thêm (nếu có).
        $page_name_for_seo = 'cart';
        $page_title = 'Giỏ Hàng Của Bạn';

        // getCartItems() đã có logic tự động xóa/cập nhật item không hợp lệ
        $cartItems = $this->cartModel->getCartItems();
        $totalAmount = 0;
        foreach($cartItems as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        
        // Cập nhật lại số lượng trên session sau khi getCartItems có thể đã thay đổi giỏ hàng
        $_SESSION['cart_item_count'] = $this->cartModel->getTotalItemCount();

        $data = [
            'cartItems' => $cartItems,
            'totalAmount' => $totalAmount,
        ];
        
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/cart.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Xử lý việc thêm sản phẩm vào giỏ hàng (nếu bạn thêm lại nút này).
     * URL: /cart/add (POST)
     */
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

            if ($productId > 0 && $quantity > 0) {
                $product = $this->productModel->findProductById($productId); // findProductById đã bao gồm available_stock
                if ($product) {
                    // CartModel::addToCart đã có logic kiểm tra tồn kho từ recovery_codes
                    if ($this->cartModel->addToCart($productId, $quantity)) {
                        set_flash_message('cart_success', 'Đã thêm sản phẩm "'.htmlspecialchars($product['name']).'" vào giỏ hàng!', MSG_SUCCESS);
                    } 
                    // flash message cho trường hợp lỗi đã được đặt bên trong CartModel
                } else {
                    set_flash_message('cart_error', 'Sản phẩm không hợp lệ.', MSG_ERROR);
                }
            } else {
                set_flash_message('cart_error', 'Dữ liệu không hợp lệ.', MSG_ERROR);
            }
            
            // Cập nhật số lượng trên session
            $_SESSION['cart_item_count'] = $this->cartModel->getTotalItemCount();
            
            // Chuyển hướng về trang trước đó hoặc trang giỏ hàng
            $redirect_url = isset($_POST['return_url']) && !empty($_POST['return_url']) ? sanitize_input($_POST['return_url']) : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : base_url('cart'));
            
            // Tránh redirect lại chính action 'cart/add'
            if (strpos($redirect_url, 'cart/add') !== false || empty($redirect_url)) {
                $redirect_url = base_url('cart');
            }
            redirect($redirect_url);
        } else {
            redirect(base_url()); // Chuyển về trang chủ nếu truy cập trực tiếp
        }
    }

    /**
     * Cập nhật số lượng của một item trong giỏ hàng.
     * URL: /cart/update (POST)
     */
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

            if ($cartItemId > 0) {
                // CartModel::updateItemQuantity đã có logic kiểm tra tồn kho
                if ($this->cartModel->updateItemQuantity($cartItemId, $quantity)) {
                    if ($quantity > 0) {
                        set_flash_message('cart_success', 'Cập nhật số lượng thành công.', MSG_SUCCESS);
                    } else {
                        set_flash_message('cart_success', 'Sản phẩm đã được xóa khỏi giỏ hàng.', MSG_SUCCESS);
                    }
                }
                // flash message cho trường hợp lỗi đã được đặt bên trong CartModel
            } else {
                set_flash_message('cart_error', 'Dữ liệu không hợp lệ.', MSG_ERROR);
            }
            
            $_SESSION['cart_item_count'] = $this->cartModel->getTotalItemCount();
            redirect(base_url('cart'));
        } else {
            redirect(base_url('cart'));
        }
    }

    /**
     * Xóa một item khỏi giỏ hàng.
     * URL: /cart/remove (POST)
     */
    public function remove() {
         if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $cartItemId = isset($_POST['cart_item_id']) ? (int)$_POST['cart_item_id'] : 0;
            if ($cartItemId > 0) {
                if ($this->cartModel->removeItem($cartItemId)) {
                    set_flash_message('cart_success', 'Sản phẩm đã được xóa khỏi giỏ hàng.', MSG_SUCCESS);
                } else {
                    set_flash_message('cart_error', 'Không thể xóa sản phẩm khỏi giỏ hàng.', MSG_ERROR);
                }
                $_SESSION['cart_item_count'] = $this->cartModel->getTotalItemCount();
            } else {
                set_flash_message('cart_error', 'Sản phẩm không hợp lệ.', MSG_ERROR);
            }
            redirect(base_url('cart'));
        } else {
            redirect(base_url('cart'));
        }
    }

    /**
     * Xóa toàn bộ giỏ hàng.
     * URL: /cart/clear (POST)
     */
    public function clear() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
             redirect(base_url('cart'));
        }
        if ($this->cartModel->clearCart()) {
            set_flash_message('cart_info', 'Giỏ hàng của bạn đã được làm trống.', MSG_INFO);
        } else {
            set_flash_message('cart_error', 'Không thể làm trống giỏ hàng.', MSG_ERROR);
        }
        $_SESSION['cart_item_count'] = 0;
        redirect(base_url('cart'));
    }

    /**
     * API endpoint để lấy số lượng item trong giỏ (dùng cho AJAX).
     * URL: /cart/item-count
     */
    public function item_count() {
        header('Content-Type: application/json');
        $count = $this->cartModel->getTotalItemCount();
        $_SESSION['cart_item_count'] = $count; // Đồng bộ session
        echo json_encode(['item_count' => $count]);
        exit;
    }
}
?>