<?php
// /views/login.php
// $data được truyền từ UserController::login()
// $page_title cũng đã được đặt
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h3 class="mb-0"><?php echo htmlspecialchars($page_title); ?></h3>
            </div>
            <div class="card-body">
                <?php echo display_flash_message('register_success'); // Hiển thị thông báo nếu vừa đăng ký xong ?>
                <?php echo display_flash_message('global_info'); // Hiển thị thông báo nếu vừa đăng xuất ?>

                <?php if (!empty($data['errors']['form'])): ?>
                    <div class="alert alert-danger"><?php echo $data['errors']['form']; ?></div>
                <?php endif; ?>

                <form action="<?php echo base_url('user/login'); ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="username_or_email" class="form-label">Tên đăng nhập hoặc Email <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($data['errors']['username_or_email']) ? 'is-invalid' : ''; ?>" id="username_or_email" name="username_or_email" value="<?php echo htmlspecialchars($data['username_or_email']); ?>" required autofocus>
                        <?php if (!empty($data['errors']['username_or_email'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['username_or_email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control <?php echo !empty($data['errors']['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                        <?php if (!empty($data['errors']['password'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['password']; ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me" <?php echo $data['remember_me'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="remember_me">Nhớ đăng nhập</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success btn-lg">Đăng Nhập</button>
                    </div>
                    <div class="mt-3 text-center">
                        <a href="<?php echo base_url('user/forgot-password'); ?>">Quên mật khẩu?</a> </div>
                </form>
            </div>
            <div class="card-footer text-center">
                Chưa có tài khoản? <a href="<?php echo base_url('user/register'); ?>">Đăng ký tại đây</a>
            </div>
        </div>
    </div>
</div>
