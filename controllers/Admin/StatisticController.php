<?php
// /controllers/Admin/StatisticController.php

class StatisticController extends AdminBaseController {
    private $userModel;
    private $productModel;
    private $transactionModel;

    public function __construct() {
        parent::__construct();
        
        if (class_exists('UserModel')) $this->userModel = new UserModel();
            else die('FATAL ERROR: Class UserModel không tìm thấy trong StatisticController.');
        if (class_exists('ProductModel')) $this->productModel = new ProductModel();
            else die('FATAL ERROR: Class ProductModel không tìm thấy trong StatisticController.');
        if (class_exists('TransactionModel')) $this->transactionModel = new TransactionModel();
            else die('FATAL ERROR: Class TransactionModel không tìm thấy trong StatisticController.');
    }

    /**
     * Hiển thị trang thống kê chính.
     * URL: /admin/statistic hoặc /admin/statistic/index
     */
    public function index() {
        $pageTitle = "Thống Kê Hệ Thống";
        $currentPageTitle = "Báo Cáo & Thống Kê";

        // Lấy dữ liệu thống kê cơ bản
        $data = [
            'total_users' => $this->userModel->getTotalUsersCount(),
            'total_products_type' => $this->productModel->adminGetTotalProductsCount(),
            
            // ĐÃ SỬA: Gọi đúng tên phương thức mới
            'total_active_products_type' => $this->productModel->getTotalActiveProductTypesCount(),
            
            'total_transactions' => $this->transactionModel->adminGetTotalTransactionsCount(),
            'completed_purchase_transactions' => $this->transactionModel->adminGetTotalTransactionsCount(['status' => TRANSACTION_STATUS_COMPLETED, 'transaction_type' => TRANSACTION_TYPE_PURCHASE]),
            'completed_deposit_transactions' => $this->transactionModel->adminGetTotalTransactionsCount(['status' => TRANSACTION_STATUS_COMPLETED, 'transaction_type' => TRANSACTION_TYPE_DEPOSIT]),
            
            'total_revenue_completed' => $this->transactionModel->adminGetTotalRevenue(),

            // Dữ liệu mẫu cho biểu đồ
            'revenue_by_month_labels' => json_encode(['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']),
            'revenue_by_month_data' => json_encode([
                rand(100,5000), rand(100,5000), rand(100,5000), rand(100,5000), 
                rand(100,5000), rand(100,5000), rand(100,5000), rand(100,5000), 
                rand(100,5000), rand(100,5000), rand(100,5000), rand(100,5000)
            ]), 

            // Dữ liệu cho biểu đồ vai trò người dùng
            'user_role_admin_count' => $this->userModel->getCountByRole(ROLE_ADMIN),
            'user_role_user_count' => $this->userModel->getCountByRole(ROLE_USER),
        ];
        
        $this->loadAdminView('statistics', $data, $pageTitle, $currentPageTitle);
    }
}
?>