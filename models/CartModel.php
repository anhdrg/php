<?php
// /models/CartModel.php

class CartModel {
    private $db;
    private $current_user_id;
    private $current_session_id; // Dùng để định danh giỏ hàng của khách

    public function __construct() {
        $this->db = new Database();
        $this->current_user_id = getCurrentUser('id'); 

        if (!$this->current_user_id) { // Nếu là khách
            if (session_status() == PHP_SESSION_NONE) { session_start(); } // Đảm bảo session đã bắt đầu
            if (!isset($_SESSION['cart_session_id'])) {
                // Tạo session_id duy nhất cho giỏ hàng của khách nếu chưa có
                $_SESSION['cart_session_id'] = bin2hex(random_bytes(16)); 
            }
            $this->current_session_id = $_SESSION['cart_session_id'];
        } else {
            $this->current_session_id = null; // User đã đăng nhập, không dùng session_id cho giỏ hàng nữa
        }
    }

    /**
     * Lấy session_id hiện tại của giỏ hàng (dùng khi gộp giỏ).
     */
    public function getGuestCartSessionId() {
        // Hàm này có thể cần nếu bạn muốn lấy session_id của giỏ hàng khách trước khi họ đăng nhập
        // Hiện tại, constructor đã xử lý việc gán current_session_id
        return $_SESSION['cart_session_id'] ?? null; // Lấy từ session nếu tồn tại
    }

    /**
     * Thêm sản phẩm vào giỏ hàng hoặc cập nhật số lượng nếu đã tồn tại.
     * @param int $productId ID của Loại Sản Phẩm.
     * @param int $quantity Số lượng mã game muốn mua.
     * @return bool
     */
    public function addToCart($productId, $quantity = 1) {
        if ($quantity <= 0) return false;

        $productModel = new ProductModel(); // Để kiểm tra thông tin loại sản phẩm
        $productType = $productModel->findProductById($productId); // findProductById đã bao gồm available_stock

        if (!$productType || !$productType['is_active']) { // Kiểm tra is_active của Product Type
            set_flash_message('cart_error', 'Loại sản phẩm không tồn tại hoặc không hoạt động.', MSG_ERROR);
            return false;
        }
        // available_stock đã được tính trong findProductById (số lượng mã game có sẵn)
        $actualAvailableStock = $productType['available_stock'] ?? 0;


        $currentQuantityInCart = 0;
        $existingCartItemId = null;

        // Xác định điều kiện WHERE và tham số dựa trên user đăng nhập hay khách
        $whereClause = "";
        $params = [':product_id' => $productId];
        if ($this->current_user_id) {
            $whereClause = "user_id = :identifier AND product_id = :product_id";
            $params[':identifier'] = $this->current_user_id;
        } elseif ($this->current_session_id) {
            $whereClause = "session_id = :identifier AND product_id = :product_id";
            $params[':identifier'] = $this->current_session_id;
        } else {
            // Không có định danh user hoặc session, không thể thêm vào giỏ
            set_flash_message('cart_error', 'Lỗi không xác định được giỏ hàng.', MSG_ERROR);
            return false;
        }

        $this->db->query("SELECT id, quantity FROM carts WHERE " . $whereClause);
        foreach ($params as $key => $value) { $this->db->bind($key, $value); }
        $existingItem = $this->db->single();

        if ($existingItem) {
            $existingCartItemId = $existingItem['id'];
            $currentQuantityInCart = $existingItem['quantity'];
        }
        
        $newTotalQuantity = $currentQuantityInCart + $quantity;

        if ($actualAvailableStock < $newTotalQuantity) {
            set_flash_message('cart_error', 'Không đủ số lượng mã game cho loại sản phẩm "'.htmlspecialchars($productType['name']).'". Chỉ còn '.$actualAvailableStock.' mã. Trong giỏ của bạn đã có '.$currentQuantityInCart.'.', MSG_WARNING);
            return false;
        }

        if ($existingItem) {
            // Sản phẩm đã có, cập nhật số lượng
            $this->db->query('UPDATE carts SET quantity = :quantity WHERE id = :id');
            $this->db->bind(':quantity', $newTotalQuantity);
            $this->db->bind(':id', $existingCartItemId);
        } else {
            // Sản phẩm chưa có, thêm mới
            $sqlInsert = 'INSERT INTO carts (product_id, quantity, ';
            $sqlValues = 'VALUES (:product_id, :quantity, ';
            if ($this->current_user_id) {
                $sqlInsert .= 'user_id) '; $sqlValues .= ':identifier)';
            } else { // Khách
                $sqlInsert .= 'session_id) '; $sqlValues .= ':identifier)';
            }
            $this->db->query($sqlInsert . $sqlValues);
            $this->db->bind(':product_id', $productId);
            $this->db->bind(':quantity', $quantity); // Số lượng ban đầu thêm vào là $quantity
            $this->db->bind(':identifier', $this->current_user_id ?: $this->current_session_id);
        }

        try {
            return $this->db->execute();
        } catch (PDOException $e) {
            write_log("Lỗi thêm/cập nhật giỏ hàng: " . $e->getMessage(), 'ERROR');
            set_flash_message('cart_error', 'Lỗi khi xử lý giỏ hàng. Vui lòng thử lại.', MSG_ERROR);
            return false;
        }
    }

    /**
     * Lấy tất cả sản phẩm trong giỏ hàng.
     * @return array Danh sách sản phẩm trong giỏ kèm thông tin chi tiết.
     */
    public function getCartItems() {
        $sql = "SELECT c.id as cart_item_id, c.quantity, 
                       p.id as product_id, p.name, p.price, p.image_url, p.is_active as product_is_active,
                       (SELECT COUNT(rc.id) FROM recovery_codes rc WHERE rc.product_id = p.id AND rc.status = 'available') as actual_available_stock
                FROM carts c
                JOIN products p ON c.product_id = p.id
                WHERE ";

        $identifierParam = null;
        if ($this->current_user_id) {
            $sql .= "c.user_id = :identifier";
            $identifierParam = $this->current_user_id;
        } elseif ($this->current_session_id) {
            $sql .= "c.session_id = :identifier";
            $identifierParam = $this->current_session_id;
        } else {
            return []; // Không có user hay session_id hợp lệ
        }
        
        $this->db->query($sql);
        $this->db->bind(':identifier', $identifierParam);
        $items = $this->db->resultSet();

        $validItems = [];
        $cartUpdatedDueToStock = false;

        foreach ($items as $item) {
            // Kiểm tra xem loại sản phẩm còn active không
            if (!$item['product_is_active']) {
                $this->removeItem($item['cart_item_id']); // Xóa khỏi giỏ nếu loại SP không active
                set_flash_message('cart_updated_item_' . $item['product_id'], "Loại sản phẩm '".htmlspecialchars($item['name'])."' không còn bán và đã được xóa khỏi giỏ.", MSG_WARNING);
                $cartUpdatedDueToStock = true;
                continue;
            }

            // Kiểm tra số lượng tồn kho thực tế của mã game
            if ($item['actual_available_stock'] == 0) {
                $this->removeItem($item['cart_item_id']); // Xóa khỏi giỏ nếu hết mã game
                set_flash_message('cart_updated_item_' . $item['product_id'], "Loại sản phẩm '".htmlspecialchars($item['name'])."' đã hết mã và được xóa khỏi giỏ.", MSG_WARNING);
                $cartUpdatedDueToStock = true;
                continue;
            }
            
            if ($item['quantity'] > $item['actual_available_stock']) {
                $item['quantity'] = $item['actual_available_stock']; // Giảm số lượng trong giỏ bằng tồn kho
                $this->updateItemQuantityInternal($item['cart_item_id'], $item['actual_available_stock'], false); // Cập nhật DB, không check stock lại
                set_flash_message('cart_updated_item_' . $item['product_id'], "Số lượng loại sản phẩm '".htmlspecialchars($item['name'])."' đã được cập nhật còn ".$item['actual_available_stock']." do thay đổi tồn kho.", MSG_WARNING);
                $cartUpdatedDueToStock = true;
            }
            $validItems[] = $item;
        }
        
        if ($cartUpdatedDueToStock) {
             // set_flash_message('cart_updated', 'Một số sản phẩm trong giỏ hàng của bạn đã được tự động cập nhật hoặc xóa do thay đổi về tình trạng hoặc tồn kho.', MSG_INFO);
        }
        return $validItems;
    }
    
    // Hàm nội bộ, không check stock lại khi được gọi từ getCartItems
    private function updateItemQuantityInternal($cartItemId, $quantity, $checkStockAgain = true) {
        if ($quantity <= 0) {
            return $this->removeItem($cartItemId);
        }

        if ($checkStockAgain) {
            $this->db->query('SELECT c.product_id, p.is_active, (SELECT COUNT(rc.id) FROM recovery_codes rc WHERE rc.product_id = p.id AND rc.status = \'available\') as actual_stock 
                              FROM carts c JOIN products p ON c.product_id = p.id 
                              WHERE c.id = :cart_item_id');
            $this->db->bind(':cart_item_id', $cartItemId);
            $itemInfo = $this->db->single();

            if (!$itemInfo || !$itemInfo['is_active']) { set_flash_message('cart_error', 'Loại sản phẩm không tồn tại hoặc không hoạt động.', MSG_ERROR); return false;}
            if ($itemInfo['actual_stock'] < $quantity) { set_flash_message('cart_error', 'Không đủ số lượng mã game trong kho.', MSG_ERROR); return false; }
        }

        $this->db->query('UPDATE carts SET quantity = :quantity WHERE id = :id');
        $this->db->bind(':quantity', $quantity, PDO::PARAM_INT);
        $this->db->bind(':id', $cartItemId, PDO::PARAM_INT);
        try { return $this->db->execute(); } 
        catch (PDOException $e) { write_log("Lỗi cập nhật số lượng giỏ hàng: " . $e->getMessage(), 'ERROR'); return false; }
    }

    public function updateItemQuantity($cartItemId, $quantity) {
        return $this->updateItemQuantityInternal($cartItemId, $quantity, true);
    }

    public function removeItem($cartItemId) {
        $sql_where = "id = :cart_item_id";
        if ($this->current_user_id) { $sql_where .= " AND user_id = :identifier"; $identifier = $this->current_user_id;}
        elseif($this->current_session_id) { $sql_where .= " AND session_id = :identifier"; $identifier = $this->current_session_id;}
        else { return false; }

        $this->db->query("DELETE FROM carts WHERE " . $sql_where);
        $this->db->bind(':cart_item_id', $cartItemId, PDO::PARAM_INT);
        $this->db->bind(':identifier', $identifier);
        try { return $this->db->execute(); } 
        catch (PDOException $e) { write_log("Lỗi xóa sản phẩm khỏi giỏ: " . $e->getMessage(), 'ERROR'); return false; }
    }

    public function clearCart() {
        $sql_where = "";
        if ($this->current_user_id) { $sql_where = 'user_id = :identifier'; $identifier = $this->current_user_id;}
        elseif ($this->current_session_id) { $sql_where = 'session_id = :identifier'; $identifier = $this->current_session_id;}
        else { return true; } // Không có gì để xóa

        $this->db->query('DELETE FROM carts WHERE ' . $sql_where);
        $this->db->bind(':identifier', $identifier);
        try { return $this->db->execute(); } 
        catch (PDOException $e) { write_log("Lỗi xóa toàn bộ giỏ hàng: " . $e->getMessage(), 'ERROR'); return false; }
    }

    public function getTotalItemCount() {
        $sql_where = ""; $identifier = null;
        if ($this->current_user_id) {
            $sql_where = 'c.user_id = :identifier';
            $identifier = $this->current_user_id;
        } elseif ($this->current_session_id) {
            $sql_where = 'c.session_id = :identifier';
            $identifier = $this->current_session_id;
        } else {
            return 0;
        }
        // Chỉ đếm các item trong giỏ mà loại sản phẩm tương ứng vẫn active VÀ còn mã game available
        $sql = "SELECT SUM(c.quantity) as total_items 
                FROM carts c 
                JOIN products p ON c.product_id = p.id
                WHERE {$sql_where} AND p.is_active = TRUE 
                AND EXISTS (SELECT 1 FROM recovery_codes rc WHERE rc.product_id = p.id AND rc.status = 'available')";
        
        $this->db->query($sql);
        if ($identifier !== null) {
            $this->db->bind(':identifier', $identifier);
        }
        $result = $this->db->single();
        return $result && $result['total_items'] ? (int)$result['total_items'] : 0;
    }

    public function getTotalAmount() {
        $items = $this->getCartItems(); // getCartItems đã validate và cập nhật số lượng
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += $item['price'] * $item['quantity'];
        }
        return $totalAmount;
    }

    public function mergeSessionCartToUserCart($sessionId, $userId) {
        $this->db->query('SELECT product_id, quantity FROM carts WHERE session_id = :session_id');
        $this->db->bind(':session_id', $sessionId);
        $sessionItems = $this->db->resultSet();

        if (empty($sessionItems)) { return true; }

        $productModel = new ProductModel();
        $this->db->beginTransaction();
        try {
            foreach ($sessionItems as $sItem) {
                $productType = $productModel->findProductById($sItem['product_id']);
                if (!$productType || !$productType['is_active'] || ($productType['available_stock'] ?? 0) == 0) {
                    continue; // Bỏ qua nếu loại sản phẩm không còn bán hoặc hết mã
                }
                $actualAvailableStock = $productType['available_stock'];

                $this->db->query('SELECT id, quantity FROM carts WHERE user_id = :user_id AND product_id = :product_id');
                $this->db->bind(':user_id', $userId);
                $this->db->bind(':product_id', $sItem['product_id']);
                $userItem = $this->db->single();

                $quantityFromSession = $sItem['quantity'];

                if ($userItem) {
                    $newTotalQuantity = $userItem['quantity'] + $quantityFromSession;
                    $finalQuantity = min($newTotalQuantity, $actualAvailableStock);
                    if ($finalQuantity > $userItem['quantity']) { // Chỉ update nếu số lượng mới lớn hơn
                        $this->db->query('UPDATE carts SET quantity = :quantity WHERE id = :id');
                        $this->db->bind(':quantity', $finalQuantity);
                        $this->db->bind(':id', $userItem['id']);
                        $this->db->execute();
                    }
                } else {
                    $finalQuantity = min($quantityFromSession, $actualAvailableStock);
                    if ($finalQuantity > 0) {
                        $this->db->query('INSERT INTO carts (user_id, product_id, quantity) VALUES (:user_id, :product_id, :quantity)');
                        $this->db->bind(':user_id', $userId);
                        $this->db->bind(':product_id', $sItem['product_id']);
                        $this->db->bind(':quantity', $finalQuantity);
                        $this->db->execute();
                    }
                }
            }
            $this->db->query('DELETE FROM carts WHERE session_id = :session_id');
            $this->db->bind(':session_id', $sessionId);
            $this->db->execute();
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            write_log("Lỗi gộp giỏ hàng: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
}
?>