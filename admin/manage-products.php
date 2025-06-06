<?php
// /admin/manage-products.php
// Dữ liệu được truyền từ AdminProductController::index():
// $data['products'], $data['currentPage'], $data['totalPages'], $data['totalProducts'], $data['searchTerm']
// $admin_page_title và $admin_current_page_title đã được controller (thông qua AdminBaseController) đặt.
?>

<div class="container-fluid">
    <?php echo display_flash_message('admin_product_success'); ?>
    <?php echo display_flash_message('admin_product_error'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Loại Sản Phẩm (Tổng: <?php echo $data['totalProducts'] ?? 0; ?>)</h6>
            <a href="<?php echo base_url('admin/product/add'); // Link đến action add của AdminProductController ?>" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Thêm Loại Sản Phẩm Mới
            </a>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <form action="<?php echo base_url('admin/product'); // Form tìm kiếm trỏ về action index của AdminProductController ?>" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Tìm theo ID, tên loại..." value="<?php echo htmlspecialchars($data['searchTerm'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-sm w-100">Tìm Kiếm</button>
                    </div>
                     <?php if (!empty($data['searchTerm'])): ?>
                        <div class="col-md-2">
                            <a href="<?php echo base_url('admin/product'); ?>" class="btn btn-outline-secondary btn-sm w-100">Xóa Tìm</a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($data['products'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="dataTableProductTypes" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Ảnh</th>
                                <th>Tên Loại Sản Phẩm</th>
                                <th class="text-end">Giá / 1 Mã</th>
                                <th class="text-center">Mã Còn Lại</th>
                                <th class="text-center">Tổng Mã Đã Nhập</th>
                                <th class="text-center">Trạng Thái</th>
                                <th class="text-center" style="width: 18%;">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['products'] as $product_type): ?>
                                <tr>
                                    <td><?php echo $product_type['id']; ?></td>
                                    <td>
                                        <img src="<?php echo !empty($product_type['image_url']) ? asset_url($product_type['image_url']) : asset_url('images/placeholder_thumb.jpg'); ?>"
                                             alt="<?php echo htmlspecialchars($product_type['name']); ?>" style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td><?php echo htmlspecialchars($product_type['name']); ?></td>
                                    <td class="text-end"><?php echo format_currency($product_type['price']); ?></td>
                                    <td class="text-center fw-bold <?php echo ($product_type['available_stock'] ?? 0) > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $product_type['available_stock'] ?? 0; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php echo $product_type['total_codes_linked'] ?? 0; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($product_type['is_active'])): ?>
                                            <span class="badge bg-success">Đang hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Không hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('admin/game-code?product_id_filter=' . $product_type['id']); // Link đến quản lý kho mã game cho loại này ?>" class="btn btn-info btn-sm mb-1" title="Quản lý kho mã cho loại này">
                                            <i class="fas fa-archive"></i> Kho Mã
                                        </a>
                                        <a href="<?php echo base_url('admin/product/edit/' . $product_type['id']); // ĐÃ SỬA ĐÚNG LINK ?>" class="btn btn-warning btn-sm" title="Sửa loại sản phẩm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="<?php echo base_url('admin/product/delete'); // Form POST đến action delete của AdminProductController ?>" method="POST" class="d-inline delete-product-form ms-1">
                                            <input type="hidden" name="product_id" value="<?php echo $product_type['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa loại sản phẩm"
                                                    onclick="return confirm('Bạn có chắc muốn xóa LOẠI SẢN PHẨM \'<?php echo htmlspecialchars(addslashes($product_type['name'])); ?>\' (ID: <?php echo $product_type['id']; ?>)? \nLƯU Ý: Hành động này chỉ thành công nếu không còn mã game nào trong kho thuộc loại này.');">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (($data['totalPages'] ?? 0) > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $queryParams = ['search' => $data['searchTerm'] ?? '']; 
                            if (($data['currentPage'] ?? 1) > 1): 
                                $queryParams['page'] = ($data['currentPage'] ?? 1) - 1; ?>
                                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Trước</a></li>
                            <?php else: ?>
                                <li class="page-item disabled"><span class="page-link">Trước</span></li>
                            <?php endif; ?>
                            <?php 
                            $num_links = 2; 
                            $start = max(1, ($data['currentPage'] ?? 1) - $num_links); 
                            $end = min(($data['totalPages'] ?? 1), ($data['currentPage'] ?? 1) + $num_links);
                            if ($start > 1): $queryParams['page'] = 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
                            for ($i = $start; $i <= $end; $i++): $queryParams['page'] = $i; ?>
                                <li class="page-item <?php echo ($i == ($data['currentPage'] ?? 1) ? 'active' : ''); ?>"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>"><?php echo $i; ?></a></li>
                            <?php endfor;
                            if ($end < ($data['totalPages'] ?? 1)): if ($end < ($data['totalPages'] ?? 1) - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; $queryParams['page'] = ($data['totalPages'] ?? 1); ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>"><?php echo ($data['totalPages'] ?? 1); ?></a></li><?php endif; ?>
                            <?php if (($data['currentPage'] ?? 1) < ($data['totalPages'] ?? 1)): $queryParams['page'] = ($data['currentPage'] ?? 1) + 1; ?>
                                <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Sau</a></li>
                            <?php else: ?>
                                <li class="page-item disabled"><span class="page-link">Sau</span></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center">
                    Không tìm thấy loại sản phẩm nào.
                    <?php if (!empty($data['searchTerm'])): ?>
                        Hãy thử tìm kiếm với từ khóa khác hoặc <a href="<?php echo base_url('admin/product'); ?>">xóa bộ lọc tìm kiếm</a>.
                    <?php else: ?>
                        <a href="<?php echo base_url('admin/product/add'); ?>">Thêm loại sản phẩm mới ngay!</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>