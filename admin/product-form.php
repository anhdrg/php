
<?php
// /admin/product-form.php
$product_type = $data['product'] ?? [
    'id' => null, 'name' => '', 'description' => '', 'price' => '', 
    'image_url' => null, 'video_url' => '', 
    'category_id' => null, 'is_active' => true, 'gallery_images' => []
];
$errors = $data['errors'] ?? [];
$gallery_images_current = $product_type['gallery_images'] ?? [];
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="<?php echo base_url('admin/product'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Danh sách Loại Sản Phẩm
        </a>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($data['form_title'] ?? 'Form Loại Sản Phẩm'); ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['form'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($errors['form']); ?></div><?php endif; ?>
            <?php echo display_flash_message('admin_product_success'); ?>
            <?php echo display_flash_message('admin_product_error'); ?>

            <form action="<?php echo htmlspecialchars($data['form_action']); ?>" method="POST" enctype="multipart/form-data" novalidate>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">Tên Loại Sản phẩm <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?php echo !empty($errors['name']) ? 'is-invalid' : ''; ?>" id="name" name="name" value="<?php echo htmlspecialchars($product_type['name'] ?? ''); ?>" required>
                            <?php if (!empty($errors['name'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['name']); ?></div><?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Mô tả</label>
                            <textarea class="form-control <?php echo !empty($errors['description']) ? 'is-invalid' : ''; ?>" id="description" name="description" rows="5"><?php echo htmlspecialchars($product_type['description'] ?? ''); ?></textarea>
                            <?php if (!empty($errors['description'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></div><?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Link Video Review</label>
                            <input type="url" class="form-control <?php echo !empty($errors['video_url']) ? 'is-invalid' : ''; ?>" id="video_url" name="video_url" value="<?php echo htmlspecialchars($product_type['video_url'] ?? ''); ?>" placeholder="Dán link video vào đây">
                            <?php if (!empty($errors['video_url'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['video_url']); ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="price" class="form-label">Giá / 1 Mã Game (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" step="1000" min="1" class="form-control <?php echo !empty($errors['price']) ? 'is-invalid' : ''; ?>" id="price" name="price" value="<?php echo htmlspecialchars((int)($product_type['price'] ?? 0)); ?>" required>
                            <?php if (!empty($errors['price'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div><?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="product_image_upload" class="form-label">Ảnh Đại Diện Chính</label>
                            <input type="file" class="form-control form-control-sm <?php echo !empty($errors['product_image']) ? 'is-invalid' : ''; ?>" id="product_image_upload" name="product_image" accept="image/jpeg,image/png,image/gif">
                            <div class="form-text">Upload ảnh mới sẽ thay thế ảnh đại diện hiện tại.</div>
                            <?php if (!empty($errors['product_image'])): ?><div class="invalid-feedback d-block"><?php echo htmlspecialchars($errors['product_image']); ?></div><?php endif; ?>
                            <?php if (!empty($product_type['image_url'])): ?>
                                <div class="mt-2">
                                    <p class="small text-muted mb-1">Ảnh đại diện hiện tại:</p>
                                    <img src="<?php echo asset_url($product_type['image_url']); ?>" alt="Ảnh đại diện hiện tại" style="max-width: 150px; max-height: 150px; object-fit: cover; border:1px solid #ddd; padding:3px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" <?php 
                                $isChecked = true; 
                                if (isset($product_type['id'])) { 
                                    $isChecked = !empty($product_type['is_active']);
                                } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['is_active'])) {
                                     $isChecked = false;
                                }
                                echo $isChecked ? 'checked' : ''; 
                            ?>>
                            <label class="form-check-label" for="is_active">Hiển thị trên trang chủ (và cho phép bán)</label>
                        </div>
                    </div>
                </div>
                <hr class="my-4">
                <h5 class="mb-3">Thêm Ảnh Mới Vào Bộ Sưu Tập</h5>
                <div class="mb-3">
                    <label for="gallery_images_upload" class="form-label">Upload ảnh mới:</label>
                    <input type="file" class="form-control" id="gallery_images_upload" name="gallery_images[]" multiple accept="image/jpeg,image/png,image/gif">
                    <div class="form-text">Bạn có thể chọn nhiều ảnh cùng lúc. Các ảnh này sẽ được thêm vào bộ sưu tập.</div>
                </div>
                <hr>
                <div class="d-flex justify-content-end">
                    <a href="<?php echo base_url('admin/product'); ?>" class="btn btn-secondary me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo htmlspecialchars($data['submit_button_text'] ?? 'Lưu Thay Đổi'); ?>
                    </button>
                </div>
            </form>
            <?php if (isset($product_type['id']) && !empty($gallery_images_current)): ?>
            <hr class="my-4">
            <h5 class="mb-3">Các Ảnh Hiện Có Trong Bộ Sưu Tập</h5>
            <p class="small text-muted">Nhấp nút "X" để xóa ảnh khỏi bộ sưu tập của sản phẩm này.</p>
            <div class="mb-3">
                <div class="row g-2 align-items-start">
                    <?php foreach ($gallery_images_current as $image): ?>
                        <div class="col-auto text-center">
                            <div class="position-relative">
                                <img src="<?php echo asset_url($image['image_path']); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: .25rem;">
                                <form action="<?php echo base_url('admin/product/delete-gallery-image'); ?>" method="POST" class="delete-gallery-image-form position-absolute top-0 end-0 m-1">
                                    <input type="hidden" name="product_image_id" value="<?php echo $image['id']; ?>">
                                    <input type="hidden" name="product_id_redirect" value="<?php echo $product_type['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-xs p-0" style="width:20px;height:20px;font-size:0.6rem;line-height:1.5;" title="Xóa ảnh" onclick="return confirm('Xóa ảnh này khỏi bộ sưu tập?');">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            </div> </div> </div>