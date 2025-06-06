<?php
// /views/register.php
// Header đã được include bởi Controller
// $data được truyền từ UserController::register()
// $page_title cũng đã được đặt trong Controller
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><?php echo htmlspecialchars($page_title); ?></h3>
            </div>
            <div class="card-body">
                <?php if (!empty($data['errors']['form'])): ?>
                    <div class="alert alert-danger"><?php echo $data['errors']['form']; ?></div>
                <?php endif; ?>

                <form action="<?php echo base_url('user/register'); ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username" class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($data['errors']['username']) ? 'is-invalid' : ''; ?>" id="username" name="username" value="<?php echo htmlspecialchars($data['username']); ?>" required>
                        <?php if (!empty($data['errors']['username'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['username']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php echo !empty($data['errors']['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($data['email']); ?>" required>
                        <?php if (!empty($data['errors']['email'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="full_name" class="form-label">Họ và tên</label>
                        <input type="text" class="form-control <?php echo !empty($data['errors']['full_name']) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($data['full_name']); ?>">
                        <?php if (!empty($data['errors']['full_name'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['full_name']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?php echo !empty($data['errors']['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                        <?php if (!empty($data['errors']['password'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['password']; ?></div>
                        <?php else: ?>
                            <div class="form-text">Ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?php echo !empty($data['errors']['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                        <?php if (!empty($data['errors']['confirm_password'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['confirm_password']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">Đăng Ký</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                Đã có tài khoản? <a href="<?php echo base_url('user/login'); ?>">Đăng nhập ngay</a>
            </div>
        </div>
    </div>
</div>

<?php
// Footer đã được include bởi Controller
?>
