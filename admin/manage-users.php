<?php
// /admin/manage-users.php
// Dữ liệu được truyền từ AdminUserController::index():
// $data['users'], $data['currentPage'], $data['totalPages'], $data['totalUsers']
// $admin_page_title và $admin_current_page_title đã được controller đặt.
?>

<div class="container-fluid">
    <?php echo display_flash_message('admin_user_success'); ?>
    <?php echo display_flash_message('admin_user_error'); ?>
    <?php echo display_flash_message('admin_balance_success'); ?>
    <?php echo display_flash_message('admin_balance_error'); ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Người Dùng (Tổng: <?php echo $data['totalUsers'] ?? 0; ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($data['users'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableUsers" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên đăng nhập</th>
                                <th>Email</th>
                                <th>Họ và tên</th>
                                <th class="text-end">Số dư</th>
                                <th class="text-center">Vai trò</th>
                                <th>Ngày tham gia</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['users'] as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name'] ?? '<em>Chưa cập nhật</em>'); ?></td>
                                    <td class="text-end fw-bold"><?php echo format_currency($user['balance']); ?></td>
                                    <td class="text-center">
                                        <?php if ($user['role'] === ROLE_ADMIN): ?>
                                            <span class="badge bg-danger"><?php echo ucfirst(ROLE_ADMIN); ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?php echo ucfirst(ROLE_USER); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-success btn-sm btn-manage-balance" 
                                                title="Quản lý số dư"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#balanceModal"
                                                data-userid="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                        <a href="<?php echo base_url('admin/user/edit/' . $user['id']); ?>" class="btn btn-warning btn-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if (getCurrentUser('id') !== $user['id']): ?>
                                            <form action="<?php echo base_url('admin/user/delete'); ?>" method="POST" class="d-inline delete-user-form ms-1">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Xóa"
                                                        onclick="return confirm('Bạn có chắc chắn muốn xóa người dùng \'<?php echo htmlspecialchars(addslashes($user['username'])); ?>\'?');">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (($data['totalPages'] ?? 0) > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                           <?php /* ... logic phân trang ... */ ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">Không tìm thấy người dùng nào.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="balanceModal" tabindex="-1" aria-labelledby="balanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="balanceModalLabel">Cập nhật số dư cho: <span id="balanceModalUsername"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo base_url('admin/user/update-balance-manual'); ?>" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="balanceModalUserId">
                    
                    <div class="mb-3">
                        <label for="operation" class="form-label">Thao tác</label>
                        <select class="form-select" name="operation" id="operation" required>
                            <option value="add" selected>Cộng tiền vào tài khoản</option>
                            <option value="subtract">Trừ tiền khỏi tài khoản</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="amount" class="form-label">Số tiền (VNĐ) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" id="amount" placeholder="Ví dụ: 50000" min="1" step="1" required>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Lý do / Ghi chú <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="description" id="description" rows="3" placeholder="Ví dụ: Hoàn tiền đơn hàng #123, Cộng tiền khuyến mãi..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-primary">Xác nhận cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var balanceModal = document.getElementById('balanceModal');
    if (balanceModal) {
        balanceModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var userId = button.getAttribute('data-userid');
            var username = button.getAttribute('data-username');
            var modalTitleSpan = balanceModal.querySelector('#balanceModalUsername');
            var modalUserIdInput = balanceModal.querySelector('#balanceModalUserId');
            
            modalTitleSpan.textContent = username;
            modalUserIdInput.value = userId;

            var amountInput = balanceModal.querySelector('#amount');
            setTimeout(function() {
                amountInput.focus();
            }, 500);
        });
    }
});
</script>