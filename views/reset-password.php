<?php
// /views/reset-password.php
// $data được truyền từ UserController::reset_password()
// $page_title, $page_name_for_seo đã được controller đặt
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Đặt Lại Mật Khẩu'); ?></h3>
            </div>
            <div class="card-body">
                <?php echo display_flash_message('global_error'); // Hiển thị lỗi nếu token không hợp lệ ?>

                <?php if ($data['isValidToken']): ?>
                    <p>Vui lòng nhập mật khẩu mới cho tài khoản của bạn.</p>
                    <form action="<?php echo base_url('user/reset-password/' . htmlspecialchars($data['selector']) . '/' . htmlspecialchars($data['validator'])); ?>" method="POST" novalidate>
                        <div class="mb-3">
                            <label for="password" class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?php echo !empty($data['errors']['password']) ? 'is-invalid' : ''; ?>" id="password" name="password" required>
                            <div class="form-text">Ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                            <?php if (!empty($data['errors']['password'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['password']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?php echo !empty($data['errors']['confirm_password']) ? 'is-invalid' : ''; ?>" id="confirm_password" name="confirm_password" required>
                            <?php if (!empty($data['errors']['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($data['errors']['form'])): ?>
                            <div class="alert alert-danger small p-2"><?php echo $data['errors']['form']; ?></div>
                        <?php endif; ?>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Đặt Lại Mật Khẩu</button>
                        </div>
                    </form>
                <?php else: ?>
                    <?php // Thông báo lỗi token không hợp lệ đã được hiển thị bằng flash message ở trên ?>
                    <p class="text-center">Nếu bạn cho rằng đây là một lỗi, vui lòng <a href="<?php echo base_url('user/forgot-password'); ?>">thử lại yêu cầu đặt lại mật khẩu</a>.</p>
                    <div class="text-center mt-3">
                         <a href="<?php echo base_url('user/login'); ?>" class="btn btn-secondary">Quay lại Đăng nhập</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>