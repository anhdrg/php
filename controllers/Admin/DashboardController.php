<?php
// /controllers/Admin/DashboardController.php

class DashboardController extends AdminBaseController {
    private $userModel;
    private $productModel;
    private $transactionModel;

    public function __construct() {
        parent::__construct(); 
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->transactionModel = new TransactionModel();
    }

    /**
     * Hiển thị trang Dashboard chính của Admin.
     */
    public function index() { 
        $data = [
            'totalUsers' => $this->userModel->getTotalUsersCount(), 
            'totalProducts' => $this->productModel->adminGetTotalProductsCount(),
            'totalAvailableProducts' => $this->productModel->getTotalActiveProductTypesCount(),
            'totalTransactions' => $this->transactionModel->adminGetTotalTransactionsCount(),
            'totalRevenue' => $this->transactionModel->adminGetTotalRevenue(), 
            'recentTransactions' => $this->transactionModel->adminGetRecentTransactions(5)
        ];
        
        $this->loadAdminView('dashboard', $data, 'Admin Dashboard', 'Dashboard');
    }
}
?>