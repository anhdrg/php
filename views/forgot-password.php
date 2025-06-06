<?php
// /views/forgot-password.php
// $data được truyền từ UserController::forgot_password()
// $page_title, $page_name_for_seo đã được controller đặt
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Quên Mật Khẩu'); ?></h3>
            </div>
            <div class="card-body">
                <?php echo display_flash_message('forgot_password_info'); ?>
                <?php echo display_flash_message('global_error'); // Cho các lỗi chung khác ?>

                <p class="card-text">Vui lòng nhập địa chỉ email đã đăng ký của bạn. Chúng tôi sẽ gửi cho bạn một liên kết để đặt lại mật khẩu.</p>
                
                <form action="<?php echo base_url('user/forgot-password'); ?>" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">Địa chỉ Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php echo !empty($data['errors']['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($data['email'] ?? ''); ?>" required autofocus>
                        <?php if (!empty($data['errors']['email'])): ?>
                            <div class="invalid-feedback"><?php echo $data['errors']['email']; ?></div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($data['errors']['form'])): ?>
                        <div class="alert alert-danger small p-2"><?php echo $data['errors']['form']; ?></div>
                    <?php endif; ?>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-warning">Gửi Liên Kết Đặt Lại</button>
                    </div>
                </form>
            </div>
            <div class="card-footer text-center">
                <a href="<?php echo base_url('user/login'); ?>">Quay lại Đăng nhập</a>
            </div>
        </div>
    </div>
</div>