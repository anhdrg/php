<?php
// /admin/user-form.php
// Dữ liệu được truyền từ AdminController::user_edit():
// $data['user_to_edit'] (dữ liệu người dùng cần sửa)
// $data['errors'] (mảng các lỗi validation)
// $data['form_action'] (URL để submit form)
// $data['form_title'] (Tiêu đề của form)
// $admin_page_title và $admin_current_page_title đã được controller đặt.

$user = $data['user_to_edit']; // Đổi tên để dễ dùng
$errors = $data['errors'];
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($data['form_title']); ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger"><?php echo $errors['form']; ?></div>
            <?php endif; ?>

            <form action="<?php echo $data['form_action']; ?>" method="POST" novalidate>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo !empty($errors['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" required>
                            <?php if (!empty($errors['username'])): ?><div class="invalid-feedback"><?php echo $errors['username']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo !empty($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                            <?php if (!empty($errors['email'])): ?><div class="invalid-feedback"><?php echo $errors['email']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="full_name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control <?php echo !empty($errors['full_name']) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>">
                            <?php if (!empty($errors['full_name'])): ?><div class="invalid-feedback"><?php echo $errors['full_name']; ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="balance" class="form-label">Số dư (VNĐ)</label>
                            <input type="number" step="1000" min="0" class="form-control <?php echo !empty($errors['balance']) ? 'is-invalid' : ''; ?>" id="balance" name="balance" value="<?php echo htmlspecialchars($user['balance'] ?? '0'); ?>">
                             <?php if (!empty($errors['balance'])): ?><div class="invalid-feedback"><?php echo $errors['balance']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Vai trò <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo !empty($errors['role']) ? 'is-invalid' : ''; ?>" id="role" name="role" required>
                                <option value="<?php echo ROLE_USER; ?>" <?php echo (isset($user['role']) && $user['role'] == ROLE_USER) ? 'selected' : ''; ?>>User</option>
                                <option value="<?php echo ROLE_ADMIN; ?>" <?php echo (isset($user['role']) && $user['role'] == ROLE_ADMIN) ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <?php if (!empty($errors['role'])): ?><div class="invalid-feedback"><?php echo $errors['role']; ?></div><?php endif; ?>
                        </div>
                        
                        <hr>
                        <p class="text-muted"><strong>Đặt lại mật khẩu (để trống nếu không muốn thay đổi):</strong></p>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới</label>
                            <input type="password" class="form-control <?php echo !empty($errors['password']) ? 'is-invalid' : ''; ?>" id="password" name="password">
                            <div class="form-text">Ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                            <?php if (!empty($errors['password'])): ?><div class="invalid-feedback"><?php echo $errors['password']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" class="form-control <?php echo !empty($errors['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password">
                            <?php if (!empty($errors['confirm_password'])): ?><div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <a href="<?php echo base_url('admin/user'); ?>" class="btn btn-secondary me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Cập Nhật Người Dùng
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>