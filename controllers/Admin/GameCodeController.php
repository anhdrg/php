<?php
// /controllers/Admin/GameCodeController.php

class GameCodeController extends AdminBaseController {
    private $recoveryCodeModel; // Model quản lý bảng recovery_codes (kho mã game)
    private $productModel;      // Model quản lý bảng products (loại sản phẩm)

    public function __construct() {
        parent::__construct(); // Gọi constructor của AdminBaseController (kiểm tra auth, etc.)
        
        // Khởi tạo các model cần thiết
        if (class_exists('RecoveryCodeModel')) {
            $this->recoveryCodeModel = new RecoveryCodeModel();
        } else {
            // Ghi log và dừng nếu model quan trọng không tồn tại
            write_log("FATAL ERROR: Class RecoveryCodeModel không tìm thấy trong GameCodeController.", "ERROR");
            // Có thể hiển thị trang lỗi thay vì die()
            die('Lỗi hệ thống: RecoveryCodeModel không khả dụng.'); 
        }

        if (class_exists('ProductModel')) {
            $this->productModel = new ProductModel();
        } else {
            write_log("FATAL ERROR: Class ProductModel không tìm thấy trong GameCodeController.", "ERROR");
            die('Lỗi hệ thống: ProductModel không khả dụng.');
        }
    }

    /**
     * Hiển thị danh sách mã game và form tạo mã mới.
     * URL: /admin/game-code  hoặc /admin/game-code/index
     * (Trước đây là manage_recovery_codes trong AdminController)
     */
    public function index() {
        $pageTitle = "Quản Lý Kho Mã Game";
        $currentPageTitle = "Kho Mã Game / Tài Khoản";

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 15;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $filters = [];
        if (isset($_GET['product_id_filter']) && $_GET['product_id_filter'] !== '') $filters['product_id'] = (int)$_GET['product_id_filter'];
        if (isset($_GET['status_filter']) && $_GET['status_filter'] !== '') $filters['status'] = sanitize_input($_GET['status_filter']);
        if (!empty($_GET['search_code_value_filter'])) $filters['search_code_value'] = sanitize_input($_GET['search_code_value_filter']);
        if (!empty($_GET['buyer_username_filter'])) $filters['buyer_username_search'] = sanitize_input($_GET['buyer_username_filter']);
        
        $gameCodes = $this->recoveryCodeModel->adminGetAllGameCodes($itemsPerPage, $offset, $filters);
        $totalGameCodes = $this->recoveryCodeModel->adminGetTotalGameCodesCount($filters);
        $totalPages = ceil($totalGameCodes / $itemsPerPage);
        
        // Lấy danh sách tất cả các loại sản phẩm để admin chọn khi thêm mã hoặc lọc
        $allProductTypes = $this->productModel->adminGetAllProducts(10000, 0); // Lấy nhiều để không bị giới hạn

        $data = [
            'gameCodes' => $gameCodes,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalCodes' => $totalGameCodes, // View manage-game-codes.php đang dùng totalCodes
            'filters' => $filters,
            'allProductTypes' => $allProductTypes,
            'generated_codes_info' => $_SESSION['generated_codes_info'] ?? null 
        ];
        unset($_SESSION['generated_codes_info']); // Xóa sau khi đã lấy để không hiển thị lại

        // View này là /admin/manage-game-codes.php
        $this->loadAdminView('manage-game-codes', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xử lý việc tạo mã game mới.
     * URL: /admin/game-code/generate (POST)
     * (Trước đây là generate_game_codes trong AdminController)
     */
    public function generate() { // Đổi tên từ generate_game_codes cho ngắn gọn
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('admin/game-code')); // Redirect về trang danh sách mã game
        }

        $productId = isset($_POST['product_id_generate']) ? (int)$_POST['product_id_generate'] : 0;
        $codeDataInput = $_POST['code_value_input'] ?? ''; // Lấy từ input có name là code_value_input
        $adminNotesInput = sanitize_input($_POST['notes_input'] ?? ''); // Lấy từ input có name là notes_input
        
        $codesToInsert = [];
        $rawCodes = preg_split('/\r\n|\r|\n/', $codeDataInput); // Tách các mã game theo dòng mới
        foreach ($rawCodes as $singleCodeData) {
            $trimmedCodeData = trim($singleCodeData);
            if (!empty($trimmedCodeData)) { 
                $codesToInsert[] = $trimmedCodeData; 
            }
        }

        if ($productId <= 0) { 
            set_flash_message('admin_recovery_error', 'Vui lòng chọn loại sản phẩm hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/game-code')); 
            return;
        }
        if (empty($codesToInsert)) { 
            set_flash_message('admin_recovery_error', 'Vui lòng nhập ít nhất một mã game/thông tin tài khoản.', MSG_ERROR); 
            redirect(base_url('admin/game-code?product_id_filter='.$productId)); 
            return;
        }
        
        $productType = $this->productModel->findProductByIdAdmin($productId);
        if (!$productType) { 
            set_flash_message('admin_recovery_error', 'Loại sản phẩm không tồn tại.', MSG_ERROR); 
            redirect(base_url('admin/game-code')); 
            return;
        }

        $successfullyAddedCount = 0; 
        $failedCodesData = []; // Lưu lại các mã không thêm được để thông báo

        foreach ($codesToInsert as $code_value_item) { // Sửa tên biến cho rõ là đang lặp qua code_value
            $dataToSave = [
                'product_id' => $productId,
                'code_value' => $code_value_item,  // Đúng với tên cột trong DB và Model
                'notes' => $adminNotesInput,      // Đúng với tên cột trong DB và Model
                'status' => 'available'          // Mặc định khi thêm mới
            ];
            if ($this->recoveryCodeModel->addGameCode($dataToSave)) { 
                $successfullyAddedCount++;
            } else { 
                $failedCodesData[] = $code_value_item; 
            }
        }
        
        if ($successfullyAddedCount > 0) {
            $flashMessage = "Đã thêm thành công {$successfullyAddedCount} mã game cho loại sản phẩm \"" . htmlspecialchars($productType['name']) . "\".";
            // Có thể lưu chi tiết các mã đã thêm vào session nếu muốn hiển thị lại chính xác
            // $_SESSION['generated_codes_info'] = $codesToInsert; // Hoặc chỉ các mã thành công
            if (!empty($failedCodesData)) { 
                $flashMessage .= " Các mã sau không thêm được: " . implode(", ", array_map('htmlspecialchars', $failedCodesData)); 
                set_flash_message('admin_recovery_warning', $flashMessage, MSG_WARNING);
            } else { 
                set_flash_message('admin_recovery_success', $flashMessage, MSG_SUCCESS); 
            }
        } else { 
            $errorMessage = 'Không thể thêm mã game nào. ';
            if(!empty($failedCodesData)) {
                $errorMessage .= 'Lỗi với các mã: ' . implode(", ", array_map('htmlspecialchars', $failedCodesData));
            }
            set_flash_message('admin_recovery_error', $errorMessage, MSG_ERROR); 
        }
        redirect(base_url('admin/game-code?product_id_filter='.$productId)); // Redirect về trang danh sách, có thể kèm filter
    }

    /**
     * Hiển thị form sửa mã game và xử lý cập nhật.
     * URL: /admin/game-code/edit/{code_id}
     * (Trước đây là edit_game_code trong AdminController)
     */
    public function edit($code_id = null) {
        if ($code_id === null || !is_numeric($code_id)) { 
            set_flash_message('admin_recovery_error', 'ID mã game không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/game-code')); 
            return;
        }
        
        $gameCode = $this->recoveryCodeModel->adminGetGameCodeById((int)$code_id);
        if (!$gameCode) { 
            set_flash_message('admin_recovery_error', 'Không tìm thấy mã game để chỉnh sửa.', MSG_ERROR); 
            redirect(base_url('admin/game-code')); 
            return;
        }

        $pageTitle = "Chỉnh Sửa Mã Game"; 
        $currentPageTitle = "Chỉnh Sửa Mã Game #" . $gameCode['id'] . " (Thuộc loại: " . htmlspecialchars($gameCode['product_name']) . ")"; 
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $updatedData = [
                'product_id' => filter_var($_POST['product_id_edit'] ?? $gameCode['product_id'], FILTER_VALIDATE_INT),
                'code_value' => $_POST['code_value_edit'] ?? $gameCode['code_value'], // Không sanitize vì admin có thể cần nhập cấu trúc đặc biệt
                'notes' => sanitize_input($_POST['notes_edit'] ?? $gameCode['notes']),
                'status' => sanitize_input($_POST['status_edit'] ?? $gameCode['status'])
            ];

            if (empty($updatedData['code_value'])) $errors['code_value_edit'] = "Nội dung mã game không được để trống.";
            if (!in_array($updatedData['status'], ['available', 'sold', 'reserved', 'disabled'])) $errors['status_edit'] = "Trạng thái không hợp lệ.";
            if (empty($updatedData['product_id'])) $errors['product_id_edit'] = "Vui lòng chọn loại sản phẩm.";

            if (empty($errors)) {
                if ($this->recoveryCodeModel->adminUpdateGameCode((int)$code_id, $updatedData)) {
                    set_flash_message('admin_recovery_success', 'Mã game đã được cập nhật thành công!', MSG_SUCCESS);
                    redirect(base_url('admin/game-code?product_id_filter='.$updatedData['product_id']));
                } else { 
                    $errors['form'] = "Lỗi khi cập nhật mã game. Vui lòng thử lại."; 
                }
            }
            // Merge lại dữ liệu đã post vào $gameCode để hiển thị lại trên form nếu có lỗi
            $gameCode = array_merge($gameCode, $updatedData); 
        }
        
        $allProductTypes = $this->productModel->adminGetAllProducts(10000, 0);
        $data = [
            'gameCode' => $gameCode, 
            'errors' => $errors, 
            'form_action' => base_url('admin/game-code/edit/' . $code_id), 
            'form_title' => $currentPageTitle, 
            'allProductTypes' => $allProductTypes
        ];
        
        // View này là /admin/game-code-form.php
        $this->loadAdminView('game-code-form', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xử lý việc xóa một mã game.
     * URL: /admin/game-code/delete (POST với code_id)
     * (Trước đây là delete_game_code trong AdminController)
     */
    public function delete() { // Đổi tên từ delete_game_code
         if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            set_flash_message('admin_recovery_error', 'Yêu cầu không hợp lệ.', MSG_ERROR);
            redirect(base_url('admin/game-code')); 
            return;
        }
        $codeId = isset($_POST['code_id']) ? (int)$_POST['code_id'] : 0; 
        $redirectProductId = null;

        if ($codeId <= 0) { 
            set_flash_message('admin_recovery_error', 'ID mã game không hợp lệ.', MSG_ERROR);
        } else {
            $codeInfo = $this->recoveryCodeModel->adminGetGameCodeById($codeId); // Lấy thông tin trước khi xóa
            if ($codeInfo) {
                $redirectProductId = $codeInfo['product_id'];
                if ($codeInfo['status'] === 'sold') { 
                    set_flash_message('admin_recovery_error', 'Không thể xóa mã game đã bán (ID: '.$codeId.'). Bạn có thể cân nhắc chuyển sang trạng thái "vô hiệu hóa".', MSG_ERROR);
                } elseif ($this->recoveryCodeModel->adminDeleteGameCode($codeId)) { 
                    set_flash_message('admin_recovery_success', 'Mã game (ID: '.$codeId.') đã được xóa thành công.', MSG_SUCCESS);
                } else { 
                    set_flash_message('admin_recovery_error', 'Không thể xóa mã game (ID: '.$codeId.'). Vui lòng thử lại.', MSG_ERROR); 
                }
            } else {
                 set_flash_message('admin_recovery_error', 'Không tìm thấy mã game để xóa (ID: '.$codeId.').', MSG_ERROR);
            }
        }
        redirect(base_url('admin/game-code' . ($redirectProductId ? '?product_id_filter='.$redirectProductId : '' )));
    }
}
?>