<?php
// /views/profile.php
// Dữ liệu được truyền từ UserController::profile(), chứa $data['user'] và $data['errors']
// Biến $page_title, $page_name_for_seo đã được controller đặt và có sẵn trong scope này.
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Thông Tin Tài Khoản</h4>
                </div>
                <div class="card-body">
                    <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($data['user']['username']); ?></p>
                    <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($data['user']['full_name'] ?: '<em>Chưa cập nhật</em>'); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($data['user']['email']); ?></p>
                    <p><strong>Số dư:</strong> <span class="fw-bold text-success fs-5"><?php echo format_currency($data['user']['balance']); ?></span></p>
                    <p><strong>Ngày tham gia:</strong> <?php echo date('d/m/Y H:i', strtotime($data['user']['created_at'])); ?></p>
                    <hr>
                    <a href="<?php echo base_url('payment/deposit'); ?>" class="btn btn-primary w-100 mt-2">
                        <i class="fas fa-wallet"></i> Nạp Tiền Vào Tài Khoản
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <?php 
            echo display_flash_message('profile_success');
            echo display_flash_message('password_success');
            // Hiển thị các lỗi form chung nếu có
            if (!empty($data['errors']['form_profile'])) {
                echo "<div class='alert alert-danger'>" . $data['errors']['form_profile'] . "</div>";
            }
            if (!empty($data['errors']['form_password'])) {
                echo "<div class='alert alert-danger'>" . $data['errors']['form_password'] . "</div>";
            }
            ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Cập Nhật Thông Tin Cá Nhân</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo base_url('user/profile'); ?>" method="POST" novalidate>
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mb-3">
                            <label for="profile_full_name" class="form-label">Họ và tên</label>
                            <input type="text" class="form-control <?php echo !empty($data['errors']['full_name']) ? 'is-invalid' : ''; ?>" id="profile_full_name" name="full_name" value="<?php echo htmlspecialchars($data['user']['full_name'] ?? ''); ?>">
                            <?php if (!empty($data['errors']['full_name'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['full_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="profile_email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control <?php echo !empty($data['errors']['email']) ? 'is-invalid' : ''; ?>" id="profile_email" name="email" value="<?php echo htmlspecialchars($data['user']['email'] ?? ''); ?>" required>
                             <?php if (!empty($data['errors']['email'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu Thay Đổi</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Thay Đổi Mật Khẩu</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo base_url('user/profile'); ?>" method="POST" novalidate>
                        <input type="hidden" name="change_password" value="1">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?php echo !empty($data['errors']['current_password']) ? 'is-invalid' : ''; ?>" id="current_password" name="current_password" required>
                            <?php if (!empty($data['errors']['current_password'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['current_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?php echo !empty($data['errors']['new_password']) ? 'is-invalid' : ''; ?>" id="new_password" name="new_password" required>
                            <?php if (!empty($data['errors']['new_password'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['new_password']; ?></div>
                            <?php else: ?>
                                <div class="form-text">Ít nhất 8 ký tự, bao gồm chữ hoa, chữ thường và số.</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                            <input type="password" class="form-control <?php echo !empty($data['errors']['confirm_new_password']) ? 'is-invalid' : ''; ?>" id="confirm_new_password" name="confirm_new_password" required>
                            <?php if (!empty($data['errors']['confirm_new_password'])): ?>
                                <div class="invalid-feedback"><?php echo $data['errors']['confirm_new_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-key"></i> Đổi Mật Khẩu</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>