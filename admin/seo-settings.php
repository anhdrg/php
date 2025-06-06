<?php
// /admin/seo-settings.php
// Dữ liệu được truyền từ SeoSettingController::index(): 
// $data['seoEntries'], $data['newSeoData'], $data['errors_new']
// $admin_page_title và $admin_current_page_title đã được controller đặt.
?>
<div class="container-fluid">
    <?php echo display_flash_message('admin_seo_success'); ?>
    <?php echo display_flash_message('admin_seo_error'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Thêm Cài Đặt SEO Mới</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($data['errors_new']['form_new'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($data['errors_new']['form_new']); ?></div>
            <?php endif; ?>

            <form action="<?php echo base_url('admin/seo-setting'); // Form POST về action index của SeoSettingController ?>" method="POST">
                <input type="hidden" name="add_seo_setting" value="1">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="page_name_new" class="form-label">Page Name (Unique) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php echo !empty($data['errors_new']['page_name_new']) ? 'is-invalid' : ''; ?>" id="page_name_new" name="page_name" value="<?php echo htmlspecialchars($data['newSeoData']['page_name'] ?? ''); ?>" placeholder="Ví dụ: home, product_list" required>
                        <div class="form-text">Định danh duy nhất cho trang (không dấu, không cách, dùng _ hoặc -).</div>
                        <?php if (!empty($data['errors_new']['page_name_new'])): ?><div class="invalid-feedback"><?php echo htmlspecialchars($data['errors_new']['page_name_new']); ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <label for="meta_title_new" class="form-label">Meta Title</label>
                        <input type="text" class="form-control" id="meta_title_new" name="meta_title" value="<?php echo htmlspecialchars($data['newSeoData']['meta_title'] ?? ''); ?>" placeholder="Tiêu đề trang (hiển thị trên tab trình duyệt và kết quả tìm kiếm)">
                    </div>
                    <div class="col-md-12">
                        <label for="meta_description_new" class="form-label">Meta Description</label>
                        <textarea class="form-control" id="meta_description_new" name="meta_description" rows="2" placeholder="Mô tả ngắn gọn về trang (hiển thị dưới tiêu đề trong kết quả tìm kiếm)"><?php echo htmlspecialchars($data['newSeoData']['meta_description'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-md-12">
                        <label for="meta_keywords_new" class="form-label">Meta Keywords</label>
                        <input type="text" class="form-control" id="meta_keywords_new" name="meta_keywords" value="<?php echo htmlspecialchars($data['newSeoData']['meta_keywords'] ?? ''); ?>" placeholder="Các từ khóa liên quan, cách nhau bởi dấu phẩy">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Thêm Cài Đặt SEO
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Cài Đặt SEO Hiện Có</h6>
        </div>
        <div class="card-body">
            <?php if (!empty($data['seoEntries'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Page Name</th>
                                <th>Meta Title</th>
                                <th>Meta Description (ngắn)</th>
                                <th>Cập nhật lần cuối</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['seoEntries'] as $entry): ?>
                                <tr>
                                    <td><?php echo $entry['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($entry['page_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(create_excerpt($entry['meta_title'] ?? '', 70)); ?></td>
                                    <td><?php echo htmlspecialchars(create_excerpt($entry['meta_description'] ?? '', 100)); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($entry['updated_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('admin/seo-setting/edit/' . $entry['id']); ?>" class="btn btn-warning btn-sm" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?php echo base_url('admin/seo-setting/delete'); ?>" method="POST" class="d-inline ms-1">
                                            <input type="hidden" name="setting_id" value="<?php echo $entry['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa cài đặt SEO cho trang \'<?php echo htmlspecialchars(addslashes($entry['page_name'])); ?>\'?');">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">Chưa có cài đặt SEO nào được cấu hình.</div>
            <?php endif; ?>
        </div>
    </div>
</div>