<?php
// /controllers/CheckoutController.php

class CheckoutController {
    private $cartModel;
    private $userModel;
    private $productModel;
    private $transactionModel;
    private $recoveryCodeModel;

    public function __construct() {
        $this->cartModel = new CartModel();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->transactionModel = new TransactionModel();
        $this->recoveryCodeModel = new RecoveryCodeModel();
    }

    /**
     * Xử lý yêu cầu "Mua Ngay": lưu thông tin item vào session và chuyển đến trang checkout.
     * URL: /checkout/direct_buy (POST)
     */
    public function direct_buy() {
        if (!isLoggedIn()) {
            // ... (logic redirect to login như cũ) ...
             $current_page_for_redirect = $_SERVER['HTTP_REFERER'] ?? base_url('product/list');
            if (isset($_POST['product_id']) && strpos($current_page_for_redirect, 'product/show') !== false) {
                 $_SESSION['redirect_after_login_params'] = ['product_id' => $_POST['product_id']];
            }
            $_SESSION['redirect_after_login'] = $current_page_for_redirect; 
            set_flash_message('auth_error', 'Vui lòng đăng nhập để mua hàng.', MSG_INFO);
            redirect(base_url('user/login'));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url()); 
        }

        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        // Số lượng luôn là 1 cho luồng "Mua Ngay"
        $quantity = 1; 

        if (!$productId) { // Chỉ cần kiểm tra productId vì quantity là 1
            set_flash_message('global_error', 'Thông tin sản phẩm không hợp lệ.', MSG_ERROR);
            redirect($_SERVER['HTTP_REFERER'] ?? base_url('product/list'));
        }

        $product = $this->productModel->findProductById($productId); 

        if (!$product || !isset($product['available_stock'])) {
            set_flash_message('global_error', 'Sản phẩm không tồn tại hoặc đã hết hàng.', MSG_ERROR);
            redirect($_SERVER['HTTP_REFERER'] ?? base_url('product/list'));
        }

        if ($product['available_stock'] < $quantity) { // Kiểm tra với quantity = 1
            set_flash_message('global_error', 'Sản phẩm "'.htmlspecialchars($product['name']).'" đã hết hàng.', MSG_WARNING);
            redirect($_SERVER['HTTP_REFERER'] ?? base_url('product/show/' . $productId));
        }

        // Lưu thông tin item "Mua Ngay" vào session
        $_SESSION['direct_buy_item'] = [
            'product_id'    => $product['id'],
            'name'          => $product['name'],
            'price'         => $product['price'],
            'quantity'      => $quantity, // Sẽ luôn là 1
            'image_url'     => $product['image_url'] ?? null,
            'item_total'    => $product['price'] * $quantity, // Sẽ là giá của 1 item
            'available_stock_at_buy_time' => $product['available_stock']
        ];
        
        unset($_SESSION['cart_items']); // Xóa giỏ hàng session cũ (nếu có)

        redirect(base_url('checkout'));
    }


    /**
     * Hiển thị trang checkout.
     */
    public function index() {
        if (!isLoggedIn()) { /* ... redirect to login ... */ }

        $page_name_for_seo = 'checkout';
        $page_title = 'Xác Nhận Thanh Toán'; // Đổi tiêu đề cho phù hợp
        
        $checkoutItems = [];
        $checkoutTotalAmount = 0;
        $is_direct_buy_flow = false;

        if (isset($_SESSION['direct_buy_item']) && !empty($_SESSION['direct_buy_item'])) {
            $directBuyItem = $_SESSION['direct_buy_item'];
            $is_direct_buy_flow = true;

            $productCheck = $this->productModel->findProductById($directBuyItem['product_id']);
            // quantity của directBuyItem luôn là 1
            if (!$productCheck || !isset($productCheck['available_stock']) || $productCheck['available_stock'] < 1) {
                set_flash_message('checkout_error', 'Rất tiếc, sản phẩm "'.htmlspecialchars($directBuyItem['name']).'" không còn đủ hàng. Vui lòng chọn sản phẩm khác.', MSG_ERROR);
                unset($_SESSION['direct_buy_item']); 
                redirect(base_url('product/show/' . $directBuyItem['product_id']));
            }
            $_SESSION['direct_buy_item']['available_stock_at_buy_time'] = $productCheck['available_stock'];

            $checkoutItems[] = [
                'product_id'    => $directBuyItem['product_id'],
                'name'          => $directBuyItem['name'],
                'price'         => $directBuyItem['price'],
                'quantity'      => $directBuyItem['quantity'], // Sẽ là 1
                'image_url'     => $directBuyItem['image_url'] ?? null,
            ];
            $checkoutTotalAmount = $directBuyItem['item_total'];

        } else { 
            // Nếu không có direct_buy_item và bạn đã loại bỏ hoàn toàn chức năng giỏ hàng,
            // thì nên redirect về trang sản phẩm.
            // Nếu vẫn giữ giỏ hàng, thì lấy từ CartModel.
            // Hiện tại, giả sử chỉ có "Mua Ngay", nếu không có direct_buy_item thì là lỗi hoặc người dùng vào checkout trực tiếp.
            set_flash_message('cart_empty', 'Không có sản phẩm nào để thanh toán. Vui lòng chọn sản phẩm để mua ngay.', MSG_INFO);
            redirect(base_url('product/list')); 
        }

        if (empty($checkoutItems)) {
             set_flash_message('cart_empty', 'Không có sản phẩm nào để thanh toán.', MSG_INFO);
             redirect(base_url('product/list'));
        }

        $currentUser = $this->userModel->findUserById(getCurrentUser('id'));
        // $paymentGateways được lấy để sau này có thể mở rộng, hiện tại chỉ dùng số dư
        $paymentGatewayModel = new PaymentGatewayModel();
        $paymentGateways = $paymentGatewayModel->getAllGateways(true); 

        $data = [
            'cartItems' => $checkoutItems, 
            'totalAmount' => $checkoutTotalAmount,
            'currentUser' => $currentUser,
            'paymentGateways' => $paymentGateways, 
            'is_direct_buy_flow' => $is_direct_buy_flow
        ];
        
        require_once PROJECT_ROOT . '/views/templates/header.php';
        require_once PROJECT_ROOT . '/views/checkout.php';
        require_once PROJECT_ROOT . '/views/templates/footer.php';
    }

    /**
     * Xử lý đơn hàng (Mua bằng số dư).
     */
    public function process() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(base_url('checkout')); }
        if (!isLoggedIn()) { redirect(base_url('user/login')); }

        $userId = getCurrentUser('id');
        $paymentMethod = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : null;
        if (empty($paymentMethod) || $paymentMethod !== PAYMENT_METHOD_BALANCE) { 
            set_flash_message('checkout_error', 'Chỉ hỗ trợ thanh toán bằng số dư tài khoản.', MSG_INFO);
            redirect(base_url('checkout'));
        }

        // Chỉ xử lý luồng "Mua Ngay"
        if (!isset($_SESSION['direct_buy_item']) || empty($_SESSION['direct_buy_item'])) {
            set_flash_message('checkout_error', 'Không có sản phẩm để xử lý. Vui lòng thử lại.', MSG_ERROR);
            redirect(base_url('product/list'));
        }

        $itemToProcess = $_SESSION['direct_buy_item']; // Đây là item duy nhất cần mua
        $finalTotalAmount = $itemToProcess['item_total']; // Tổng tiền chính là giá của item này (quantity=1)

        // Kiểm tra lại tồn kho lần cuối cùng trước khi thực hiện giao dịch
        $productCheck = $this->productModel->findProductById($itemToProcess['product_id']);
        if (!$productCheck || !isset($productCheck['available_stock']) || $productCheck['available_stock'] < 1) { // quantity là 1
            set_flash_message('checkout_error', 'Sản phẩm "'.htmlspecialchars($itemToProcess['name']).'" đã hết hàng khi bạn đang thanh toán.', MSG_ERROR);
            unset($_SESSION['direct_buy_item']);
            redirect(base_url('product/show/' . $itemToProcess['product_id']));
        }

        $currentUserForBalanceCheck = $this->userModel->findUserById($userId);
        if ($currentUserForBalanceCheck['balance'] < $finalTotalAmount) {
            set_flash_message('checkout_error', 'Số dư không đủ (cần '.format_currency($finalTotalAmount).'). Vui lòng <a href="'.base_url('payment/deposit').'">nạp thêm</a>.', MSG_WARNING);
            redirect(base_url('checkout'));
        }

        // Bắt đầu các thao tác CSDL quan trọng
        // (Cân nhắc sử dụng transaction CSDL ở đây nếu các model không tự quản lý tốt)
        
        // 1. Lấy và tạm giữ một mã game
        $gameCode = $this->recoveryCodeModel->getAndReserveAvailableCode($itemToProcess['product_id'], true, $userId);

        if ($gameCode) {
            // 2. Trừ tiền người dùng
            if ($this->userModel->updateUserBalance($userId, $finalTotalAmount, 'subtract')) {
                // 3. Tạo giao dịch
                $txnData = [
                    'user_id' => $userId,
                    'product_id' => $itemToProcess['product_id'],
                    'recovery_code_id' => $gameCode['id'], // ID của mã game cụ thể
                    'transaction_type' => TRANSACTION_TYPE_PURCHASE,
                    'amount' => $itemToProcess['price'], // Giá của 1 item
                    'payment_method' => PAYMENT_METHOD_BALANCE,
                    'status' => TRANSACTION_STATUS_PENDING, // Sẽ cập nhật ngay sau
                    'description' => "Mua: " . htmlspecialchars($itemToProcess['name']) . " (Mã Game ID: #" . $gameCode['id'] . ")"
                ];
                $transactionId = $this->transactionModel->createTransaction($txnData);

                if ($transactionId) {
                    // 4. Đánh dấu mã game đã bán và cập nhật trạng thái giao dịch
                    if ($this->recoveryCodeModel->markCodeAsSold($gameCode['id'], $userId, $transactionId)) {
                        $this->transactionModel->updateTransactionStatus($transactionId, TRANSACTION_STATUS_COMPLETED);
                        
                        unset($_SESSION['direct_buy_item']); // Xóa item mua ngay khỏi session
                        set_flash_message('checkout_success', 'Mua hàng thành công! Chi tiết tài khoản/mã game sẽ có trong lịch sử giao dịch của bạn.', MSG_SUCCESS);
                        $_SESSION['cart_item_count'] = $this->cartModel->getTotalItemCount(); // Cập nhật nếu bạn vẫn dùng giỏ hàng cho mục đích khác
                        redirect(base_url('user/transaction-history'));
                    } else {
                        // Lỗi nghiêm trọng: Đã trừ tiền, đã tạo giao dịch PENDING, nhưng không đánh dấu mã đã bán được
                        write_log("CRITICAL: Không thể đánh dấu mã game {$gameCode['id']} đã bán sau khi trừ tiền và tạo txn {$transactionId}.", "CRITICAL");
                        $this->transactionModel->updateTransactionStatus($transactionId, TRANSACTION_STATUS_FAILED, "MarkSoldFailed");
                        $this->userModel->updateUserBalance($userId, $finalTotalAmount, 'add'); // Hoàn tiền
                        $this->recoveryCodeModel->unreserveCode($gameCode['id']); // Hoàn lại trạng thái reserve
                        set_flash_message('checkout_error', 'Lỗi nghiêm trọng khi hoàn tất đơn hàng. Giao dịch đã được hủy và số dư đã hoàn lại. Vui lòng liên hệ hỗ trợ.', MSG_ERROR);
                        redirect(base_url('checkout'));
                    }
                } else {
                    // Lỗi tạo giao dịch: Hoàn tiền, unreserve mã
                    write_log("CRITICAL: Không thể tạo transaction sau khi trừ tiền và reserve mã {$gameCode['id']}.", "CRITICAL");
                    $this->userModel->updateUserBalance($userId, $finalTotalAmount, 'add');
                    $this->recoveryCodeModel->unreserveCode($gameCode['id']);
                    set_flash_message('checkout_error', 'Lỗi khi tạo ghi nhận giao dịch. Số dư đã được hoàn lại. Vui lòng thử lại.', MSG_ERROR);
                    redirect(base_url('checkout'));
                }
            } else {
                // Lỗi trừ số dư: Unreserve mã
                $this->recoveryCodeModel->unreserveCode($gameCode['id']);
                set_flash_message('checkout_error', 'Lỗi không thể cập nhật số dư. Vui lòng thử lại.', MSG_ERROR);
                redirect(base_url('checkout'));
            }
        } else {
            // Không lấy được mã game (hết hàng đột ngột)
            // Không cần hoàn tiền vì chưa trừ
            set_flash_message('checkout_error', 'Rất tiếc, sản phẩm vừa hết hàng khi bạn đang xử lý. Vui lòng thử lại sau.', MSG_ERROR);
            unset($_SESSION['direct_buy_item']); // Xóa item để người dùng chọn lại
            redirect(base_url('product/show/' . $itemToProcess['product_id']));
        }
    }
    
    public function deposit_success($localTransactionId = null, $gateway = null) { /* ... như cũ ... */ }
    public function deposit_cancel($localTransactionId = null, $gateway = null) { /* ... như cũ ... */ }
}
?>