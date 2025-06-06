<?php
// /admin/statistics.php
// Dữ liệu: $data['total_users'], $data['total_products'], $data['total_transactions'], 
//          $data['total_revenue_completed'], $data['revenue_by_month_labels'], $data['revenue_by_month_data']
// $admin_page_title và $admin_current_page_title đã được controller đặt.
?>
<div class="container-fluid">
    <?php // Không có flash message cụ thể cho trang này, nhưng có thể thêm nếu cần ?>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng Người Dùng</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['total_users'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Tổng Sản Phẩm</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['total_products'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-box-open fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tổng Giao Dịch</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $data['total_transactions'] ?? 0; ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-exchange-alt fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Doanh Thu (Hoàn thành)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo format_currency($data['total_revenue_completed'] ?? 0); ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-dollar-sign fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Biểu Đồ Doanh Thu Theo Tháng (Ví dụ)</h6>
                    </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="revenueChart"></canvas>
                    </div>
                    <p class="mt-2 text-center text-muted small">Đây là dữ liệu mẫu. Cần tích hợp logic lấy dữ liệu thật từ Model.</p>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Phân Bố Người Dùng (Ví dụ)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="userRoleChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="me-2"><i class="fas fa-circle text-primary"></i> Admin</span>
                        <span class="me-2"><i class="fas fa-circle text-success"></i> User</span>
                        </div>
                     <p class="mt-2 text-center text-muted small">Dữ liệu mẫu. Cần lấy số liệu từ User Model.</p>
                </div>
            </div>
        </div>
    </div>

    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Dữ liệu mẫu cho biểu đồ doanh thu (lấy từ PHP)
    const revenueLabels = <?php echo $data['revenue_by_month_labels'] ?? json_encode([]); ?>;
    const revenueData = <?php echo $data['revenue_by_month_data'] ?? json_encode([]); ?>;

    if (document.getElementById('revenueChart') && revenueLabels.length > 0 && revenueData.length > 0) {
        const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'line', // hoặc 'bar'
            data: {
                labels: revenueLabels,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenueData,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value, index, values) {
                                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value);
                            }
                        }
                    }
                }
            }
        });
    }

    // Dữ liệu mẫu cho biểu đồ vai trò người dùng
    // Cần lấy dữ liệu thực tế từ UserModel
    const userRoleData = {
        labels: ['Admin', 'User'],
        datasets: [{
            data: [<?php echo $this->userModel->getCountByRole(ROLE_ADMIN) ?? 0; ?>, <?php echo $this->userModel->getCountByRole(ROLE_USER) ?? 0; ?>], // Ví dụ dữ liệu, cần hàm trong UserModel
            backgroundColor: ['#4e73df', '#1cc88a'],
            hoverBackgroundColor: ['#2e59d9', '#17a673'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    };

    if (document.getElementById('userRoleChart') && userRoleData.datasets[0].data.some(d => d > 0)) {
        const ctxUserRole = document.getElementById('userRoleChart').getContext('2d');
        new Chart(ctxUserRole, {
            type: 'doughnut',
            data: userRoleData,
            options: {
                maintainAspectRatio: false,
                responsive: true,
                plugins: {
                    legend: {
                        display: false // Chú thích đã được thêm bên dưới biểu đồ
                    }
                },
                cutout: '80%',
            },
        });
    }
});
</script>
