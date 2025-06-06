<?php
// /admin/game-code-form.php
// Dữ liệu được truyền từ GameCodeController::edit():
// $data['gameCode'] (thông tin mã game cần sửa)
// $data['errors'] (mảng các lỗi validation)
// $data['form_action'] (URL để submit form)
// $data['form_title'] (Tiêu đề của form, ví dụ: "Chỉnh Sửa Mã Game #X")
// $data['allProductTypes'] (danh sách tất cả các loại sản phẩm để chọn)
// Biến $admin_page_title và $admin_current_page_title đã được controller đặt (thông qua $this->loadAdminView)

$gameCode = $data['gameCode'] ?? null; // Mã game hiện tại đang sửa
$errors = $data['errors'] ?? [];
$allProductTypes = $data['allProductTypes'] ?? [];

// Mảng các trạng thái có thể chọn khi sửa
$editable_game_code_statuses = [
    'available' => 'Có sẵn (Available)',
    'sold'      => 'Đã bán (Sold) - Cẩn thận khi thay đổi!',
    'reserved'  => 'Đang giữ (Reserved) - Cẩn thận!',
    'disabled'  => 'Vô hiệu hóa (Disabled)'
];

if (!$gameCode) {
    // Xử lý trường hợp không có dữ liệu mã game (dù controller nên đã kiểm tra)
    echo "<div class='container-fluid'><div class='alert alert-danger'>Không tìm thấy thông tin mã game để chỉnh sửa.</div></div>";
    // Không nên tiếp tục render form nếu không có $gameCode
    return; 
}
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="<?php echo base_url('admin/game-code' . ($gameCode['product_id'] ? '?product_id_filter='.$gameCode['product_id'] : '')); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Kho Mã Game
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($data['form_title'] ?? 'Chỉnh Sửa Mã Game'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['form']); ?></div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($data['form_action']); ?>" method="POST">
                <div class="row">
                    <div class="col-md-7">
                        <div class="mb-3">
                            <label for="code_value_edit" class="form-label">Nội dung Mã Game / Thông tin Tài khoản <span class="text-danger">*</span></label>
                            <textarea name="code_value_edit" id="code_value_edit" class="form-control <?php echo !empty($errors['code_value_edit']) ? 'is-invalid' : ''; ?>" rows="6" required><?php echo htmlspecialchars($gameCode['code_value'] ?? ''); ?></textarea>
                            <div class="form-text">Nhập thông tin chi tiết của tài khoản game hoặc mã key.</div>
                            <?php if (!empty($errors['code_value_edit'])): ?><div class="invalid-feedback"><?php echo $errors['code_value_edit']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="notes_edit" class="form-label">Ghi chú của Admin</label>
                            <textarea name="notes_edit" id="notes_edit" class="form-control" rows="3"><?php echo htmlspecialchars($gameCode['notes'] ?? ''); ?></textarea>
                            <?php if (!empty($errors['notes_edit'])): ?><div class="invalid-feedback"><?php echo $errors['notes_edit']; ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="col-md-5">
                        <div class="mb-3">
                            <label class="form-label">ID Mã Game:</label>
                            <input type="text" class="form-control-plaintext" value="#<?php echo $gameCode['id']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="product_id_edit" class="form-label">Thuộc Loại Sản Phẩm <span class="text-danger">*</span></label>
                            <select name="product_id_edit" id="product_id_edit" class="form-select <?php echo !empty($errors['product_id_edit']) ? 'is-invalid' : ''; ?>" required>
                                <option value="">-- Chọn loại sản phẩm --</option>
                                <?php if (!empty($allProductTypes)): ?>
                                    <?php foreach ($allProductTypes as $productType): ?>
                                        <option value="<?php echo $productType['id']; ?>" <?php echo (isset($gameCode['product_id']) && $gameCode['product_id'] == $productType['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($productType['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (!empty($errors['product_id_edit'])): ?><div class="invalid-feedback"><?php echo $errors['product_id_edit']; ?></div><?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="status_edit" class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="status_edit" id="status_edit" class="form-select <?php echo !empty($errors['status_edit']) ? 'is-invalid' : ''; ?>" required>
                                <?php foreach ($editable_game_code_statuses as $key => $value): ?>
                                    <option value="<?php echo $key; ?>" <?php echo (isset($gameCode['status']) && $gameCode['status'] == $key) ? 'selected' : ''; ?>><?php echo htmlspecialchars($value); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (!empty($errors['status_edit'])): ?><div class="invalid-feedback"><?php echo $errors['status_edit']; ?></div><?php endif; ?>
                        </div>
                        
                        <?php if ($gameCode['status'] === 'sold'): ?>
                            <hr>
                            <div class="mb-2">
                                <label class="form-label fw-medium">Thông tin bán:</label>
                            </div>
                            <div class="mb-1">
                                <span class="text-muted small">Người mua:</span> 
                                <?php if (!empty($gameCode['user_id_buyer']) && !empty($gameCode['buyer_username'])): ?>
                                    <a href="<?php echo base_url('admin/user-edit/' . $gameCode['user_id_buyer']); ?>"><?php echo htmlspecialchars($gameCode['buyer_username']); ?></a> (ID: <?php echo $gameCode['user_id_buyer']; ?>)
                                <?php else: echo 'N/A'; endif; ?>
                            </div>
                            <div class="mb-1">
                                <span class="text-muted small">Giao dịch #:</span> 
                                <?php if (!empty($gameCode['transaction_id'])): ?>
                                    <a href="<?php echo base_url('admin/view-transaction/' . $gameCode['transaction_id']); ?>">#<?php echo $gameCode['transaction_id']; ?></a>
                                <?php else: echo 'N/A'; endif; ?>
                            </div>
                             <div class="mb-1">
                                <span class="text-muted small">Ngày bán:</span> 
                                <?php echo $gameCode['sold_at'] ? date('d/m/Y H:i:s', strtotime($gameCode['sold_at'])) : 'N/A'; ?>
                            </div>
                        <?php endif; ?>

                        <div class="mb-1 mt-3">
                            <span class="text-muted small">Ngày tạo:</span> 
                            <?php echo date('d/m/Y H:i:s', strtotime($gameCode['created_at'])); ?>
                        </div>
                        <div class="mb-1">
                            <span class="text-muted small">Cập nhật lần cuối:</span> 
                            <?php echo date('d/m/Y H:i:s', strtotime($gameCode['updated_at'])); ?>
                        </div>

                    </div>
                </div>

                <hr>
                <div class="d-flex justify-content-end">
                    <a href="<?php echo base_url('admin/manage-recovery-codes' . ($gameCode['product_id'] ? '?product_id_filter='.$gameCode['product_id'] : '')); ?>" class="btn btn-secondary me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Thay Đổi Mã Game
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>