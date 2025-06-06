<?php
// /admin/seo-form.php
// Dữ liệu: $data['seoEntry'], $data['errors'], $data['form_action'], $data['form_title']

$seoEntry = $data['seoEntry'];
$errors = $data['errors'];
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <a href="<?php echo base_url('admin/seo-setting'); ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại Cài Đặt SEO
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><?php echo htmlspecialchars($data['form_title']); ?></h6>
        </div>
        <div class="card-body">
            <?php if (!empty($errors['form'])): ?>
                <div class="alert alert-danger"><?php echo $errors['form']; ?></div>
            <?php endif; ?>

            <form action="<?php echo $data['form_action']; ?>" method="POST">
                <div class="mb-3">
                    <label for="page_name" class="form-label">Page Name (Không thể thay đổi)</label>
                    <input type="text" class="form-control" id="page_name" name="page_name" value="<?php echo htmlspecialchars($seoEntry['page_name'] ?? ''); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="meta_title" class="form-label">Meta Title</label>
                    <input type="text" class="form-control <?php echo !empty($errors['meta_title']) ? 'is-invalid' : ''; ?>" id="meta_title" name="meta_title" value="<?php echo htmlspecialchars($seoEntry['meta_title'] ?? ''); ?>" placeholder="Tiêu đề trang (hiển thị trên tab trình duyệt và kết quả tìm kiếm)">
                    <?php if (!empty($errors['meta_title'])): ?><div class="invalid-feedback"><?php echo $errors['meta_title']; ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="meta_description" class="form-label">Meta Description</label>
                    <textarea class="form-control <?php echo !empty($errors['meta_description']) ? 'is-invalid' : ''; ?>" id="meta_description" name="meta_description" rows="3" placeholder="Mô tả ngắn gọn về trang (hiển thị dưới tiêu đề trong kết quả tìm kiếm)"><?php echo htmlspecialchars($seoEntry['meta_description'] ?? ''); ?></textarea>
                    <?php if (!empty($errors['meta_description'])): ?><div class="invalid-feedback"><?php echo $errors['meta_description']; ?></div><?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="meta_keywords" class="form-label">Meta Keywords</label>
                    <input type="text" class="form-control <?php echo !empty($errors['meta_keywords']) ? 'is-invalid' : ''; ?>" id="meta_keywords" name="meta_keywords" value="<?php echo htmlspecialchars($seoEntry['meta_keywords'] ?? ''); ?>" placeholder="Các từ khóa liên quan, cách nhau bởi dấu phẩy">
                    <div class="form-text">Mặc dù Google không còn chú trọng nhiều đến meta keywords, bạn vẫn có thể điền cho các công cụ tìm kiếm khác.</div>
                    <?php if (!empty($errors['meta_keywords'])): ?><div class="invalid-feedback"><?php echo $errors['meta_keywords']; ?></div><?php endif; ?>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="<?php echo base_url('admin/seo-setting'); ?>" class="btn btn-secondary me-2">Hủy bỏ</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu Thay Đổi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>