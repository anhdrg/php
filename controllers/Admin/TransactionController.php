<?php
// /controllers/Admin/TransactionController.php

class TransactionController extends AdminBaseController {
    private $transactionModel;
    private $userModel;
    private $productModel;
    private $recoveryCodeModel;

    public function __construct() {
        parent::__construct();
        if (class_exists('TransactionModel')) $this->transactionModel = new TransactionModel(); 
            else die('FATAL ERROR: Class TransactionModel không tìm thấy.');
        if (class_exists('UserModel')) $this->userModel = new UserModel();
            else die('FATAL ERROR: Class UserModel không tìm thấy.');
        if (class_exists('ProductModel')) $this->productModel = new ProductModel();
            else die('FATAL ERROR: Class ProductModel không tìm thấy.');
        if (class_exists('RecoveryCodeModel')) $this->recoveryCodeModel = new RecoveryCodeModel();
            else die('FATAL ERROR: Class RecoveryCodeModel không tìm thấy.');
    }

    /**
     * Hiển thị danh sách các giao dịch.
     */
    public function index() {
        $pageTitle = "Quản Lý Giao Dịch"; 
        $currentPageTitle = "Danh Sách Tất Cả Giao Dịch";

        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $itemsPerPage = defined('ADMIN_DEFAULT_ITEMS_PER_PAGE') ? ADMIN_DEFAULT_ITEMS_PER_PAGE : 15;
        $offset = ($currentPage - 1) * $itemsPerPage;

        $filters = [];
        if (!empty($_GET['search_term'])) $filters['search_term'] = sanitize_input($_GET['search_term']);
        if (!empty($_GET['user_id'])) $filters['user_id'] = (int)$_GET['user_id'];
        if (!empty($_GET['status'])) $filters['status'] = sanitize_input($_GET['status']);
        if (!empty($_GET['transaction_type'])) $filters['transaction_type'] = sanitize_input($_GET['transaction_type']);
        if (!empty($_GET['payment_method'])) $filters['payment_method'] = sanitize_input($_GET['payment_method']);
        if (!empty($_GET['date_from'])) $filters['date_from'] = sanitize_input($_GET['date_from']);
        if (!empty($_GET['date_to'])) $filters['date_to'] = sanitize_input($_GET['date_to']);
        
        $orderBy = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 't.created_at';
        $orderDir = isset($_GET['dir']) && in_array(strtoupper($_GET['dir']), ['ASC', 'DESC']) ? strtoupper($_GET['dir']) : 'DESC';
        
        $transactions = $this->transactionModel->adminGetAllTransactions($itemsPerPage, $offset, $filters, $orderBy, $orderDir);
        $totalTransactions = $this->transactionModel->adminGetTotalTransactionsCount($filters);
        $totalPages = ceil($totalTransactions / $itemsPerPage);
        
        $data = [
            'transactions' => $transactions,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalTransactions' => $totalTransactions,
            'filters' => $filters,
            'orderBy' => $orderBy,
            'orderDir' => $orderDir
        ];
        
        $this->loadAdminView('manage-transactions', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xem chi tiết một giao dịch.
     */
    public function view($id = null) {
        if ($id === null || !is_numeric($id)) { 
            set_flash_message('admin_transaction_error', 'ID giao dịch không hợp lệ.', MSG_ERROR); 
            redirect(base_url('admin/transaction'));
            return;
        }
        
        $transaction = $this->transactionModel->getTransactionById((int)$id);
        if (!$transaction) { 
            set_flash_message('admin_transaction_error', 'Không tìm thấy giao dịch.', MSG_ERROR); 
            redirect(base_url('admin/transaction'));
            return;
        }

        if (!empty($transaction['user_id'])) { $transaction['user'] = $this->userModel->findUserById($transaction['user_id']); }
        if (!empty($transaction['product_id'])) { $transaction['product_type'] = $this->productModel->findProductByIdAdmin($transaction['product_id']); }
        if (!empty($transaction['recovery_code_id'])) { $transaction['game_code_sold'] = $this->recoveryCodeModel->adminGetGameCodeById($transaction['recovery_code_id']); }

        $pageTitle = "Chi Tiết Giao Dịch";
        $currentPageTitle = "Chi Tiết Giao Dịch #" . $transaction['id'];
        $data = ['transaction' => $transaction];
        
        $this->loadAdminView('view-transaction', $data, $pageTitle, $currentPageTitle);
    }

    /**
     * Xử lý việc admin duyệt một giao dịch nạp tiền.
     */
    public function approve() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('admin/transaction'));
        }
        
        $transactionId = isset($_POST['transaction_id']) ? (int)$_POST['transaction_id'] : 0;
        
        $transaction = $this->transactionModel->getTransactionById($transactionId);
        if (!$transaction || $transaction['status'] !== TRANSACTION_STATUS_PENDING) {
            set_flash_message('admin_transaction_error', 'Giao dịch không hợp lệ hoặc không ở trạng thái chờ duyệt.', MSG_ERROR);
            redirect(base_url('admin/transaction'));
            return;
        }
        
        $db = new Database();
        $db->beginTransaction();
        
        try {
            // Cộng tiền cho user
            $this->userModel->updateUserBalance($transaction['user_id'], $transaction['amount'], 'add');
            
            // Cập nhật trạng thái giao dịch
            $this->transactionModel->updateTransactionStatus($transaction['id'], TRANSACTION_STATUS_COMPLETED, 'ADMIN_APPROVED-' . getCurrentUser('id'));

            $db->commit();
            set_flash_message('admin_transaction_success', 'Đã duyệt thành công giao dịch #' . $transactionId . ' và cộng tiền cho người dùng.', MSG_SUCCESS);

        } catch (Exception $e) {
            $db->rollBack();
            write_log("Lỗi duyệt giao dịch #{$transactionId}: " . $e->getMessage(), "ERROR");
            set_flash_message('admin_transaction_error', 'Lỗi khi duyệt giao dịch: ' . $e->getMessage(), MSG_ERROR);
        }
        
        redirect(base_url('admin/transaction'));
    }
}
?>