<?php
// /views/index.php
$is_product_list_page = isset($data['is_product_list_page']) && $data['is_product_list_page'];
$page_header_text = $is_product_list_page ? "Tất Cả Sản Phẩm" : "Sản Phẩm Nổi Bật";
if (isset($page_title) && !empty($page_title) && !$is_product_list_page) {
    $page_header_text = $page_title;
}
?>

<div class="container mt-4">
    <div class="row mb-3">
        <div class="col-md-12">
            <h2><?php echo htmlspecialchars($page_header_text); ?></h2>
            <?php if(($data['totalProducts'] ?? 0) > 0): ?>
                <p>Hiển thị <?php echo count($data['products'] ?? []); ?> trên tổng số <?php echo $data['totalProducts']; ?> loại tài khoản đang được bán.</p>
            <?php else: ?>
                 <p>Hiện chưa có loại sản phẩm nào được bày bán.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <form action="<?php echo base_url($is_product_list_page ? 'product/list' : ''); ?>" method="GET" class="row g-3 align-items-center bg-light p-3 rounded shadow-sm">
                <div class="col-md-5">
                    <label for="search" class="visually-hidden">Tìm kiếm</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Tìm kiếm theo tên, mô tả..." value="<?php echo htmlspecialchars($data['searchTerm'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label for="sort" class="visually-hidden">Sắp xếp theo</label>
                    <select class="form-select" id="sort" name="sort">
                        <option value="p.created_at" <?php echo (($data['orderBy'] ?? '') == 'p.created_at') ? 'selected' : ''; ?>>Mới nhất</option>
                        <option value="p.price" <?php echo (($data['orderBy'] ?? '') == 'p.price') ? 'selected' : ''; ?>>Giá</option>
                        <option value="p.name" <?php echo (($data['orderBy'] ?? '') == 'p.name') ? 'selected' : ''; ?>>Tên</option>
                        <option value="available_stock" <?php echo (($data['orderBy'] ?? '') == 'available_stock') ? 'selected' : ''; ?>>Số lượng còn</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="dir" class="visually-hidden">Thứ tự</label>
                    <select class="form-select" id="dir" name="dir">
                        <option value="DESC" <?php echo (strtoupper($data['orderDir'] ?? 'DESC') == 'DESC') ? 'selected' : ''; ?>>Giảm dần</option>
                        <option value="ASC" <?php echo (strtoupper($data['orderDir'] ?? 'DESC') == 'ASC') ? 'selected' : ''; ?>>Tăng dần</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Lọc</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
        <?php if (!empty($data['products'])): ?>
            <?php foreach ($data['products'] as $product): ?>
                <div class="col">
                    <div class="card h-100 shadow-hover product-card">
                        <a href="<?php echo base_url('product/show/' . $product['id']); ?>" class="text-decoration-none text-dark">
                            <img src="<?php echo !empty($product['image_url']) ? asset_url($product['image_url']) : asset_url('images/placeholder.jpg'); ?>" 
                                 class="card-img-top product-image" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title">
                                <a href="<?php echo base_url('product/show/' . $product['id']); ?>" class="text-decoration-none text-dark stretched-link">
                                    <?php echo htmlspecialchars(create_excerpt($product['name'], 50)); ?>
                                </a>
                            </h5>
                            <p class="card-text small text-muted flex-grow-1"><?php echo htmlspecialchars(create_excerpt($product['description'] ?? '', 70)); ?></p>
                            <p class="card-text fs-5 fw-bold text-danger mb-2"><?php echo format_currency($product['price']); ?></p>
                            <div class="mt-auto">
                                 <a href="<?php echo base_url('product/show/' . $product['id']); ?>" class="btn btn-sm btn-outline-primary w-100 mb-1">Xem Chi Tiết</a>
                                 <?php if (isset($product['available_stock']) && $product['available_stock'] > 0): ?>
                                     <form action="<?php echo base_url('checkout/direct_buy'); ?>" method="POST" class="d-grid">
                                         <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                         <input type="hidden" name="quantity" value="1">
                                         <button type="submit" class="btn btn-sm btn-danger w-100">
                                             <i class="fas fa-shopping-bag"></i> Mua Ngay
                                         </button>
                                     </form>
                                 <?php else: ?>
                                     <button type="button" class="btn btn-sm btn-secondary w-100" disabled>Hết hàng</button>
                                 <?php endif; ?>
                            </div>
                        </div>
                        <div class="card-footer text-muted small">
                            Còn lại: <?php echo $product['available_stock'] ?? 0; ?> mã
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center" role="alert">
                    <p class="mb-0">Không tìm thấy loại sản phẩm nào phù hợp.</p>
                    <?php if (!empty($data['searchTerm'])): ?>
                        <p>Hãy thử bỏ bớt hoặc thay đổi từ khóa tìm kiếm.</p>
                    <?php endif; ?>
                    <a href="<?php echo base_url($is_product_list_page ? 'product/list' : ''); ?>" class="btn btn-sm btn-primary mt-2">Xem tất cả</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (($data['totalPages'] ?? 0) > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php /* ... logic phân trang giữ nguyên ... */ 
                $queryParams = $_GET; 
                if ($data['currentPage'] > 1): $queryParams['page'] = $data['currentPage'] - 1; ?> <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Trước</a></li>
                <?php else: ?> <li class="page-item disabled"><span class="page-link">Trước</span></li> <?php endif; 
                $num_links = 2; $start = max(1, $data['currentPage'] - $num_links); $end = min($data['totalPages'], $data['currentPage'] + $num_links);
                if ($start > 1): $queryParams['page'] = 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
                for ($i = $start; $i <= $end; $i++): $queryParams['page'] = $i; ?> <li class="page-item <?php echo ($i == $data['currentPage'] ? 'active' : ''); ?>"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>"><?php echo $i; ?></a></li> <?php endfor;
                if ($end < $data['totalPages']): if ($end < $data['totalPages'] - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; $queryParams['page'] = $data['totalPages']; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>"><?php echo $data['totalPages']; ?></a></li><?php endif; ?>
                <?php if ($data['currentPage'] < $data['totalPages']): $queryParams['page'] = $data['currentPage'] + 1; ?> <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($queryParams); ?>">Sau</a></li>
                <?php else: ?> <li class="page-item disabled"><span class="page-link">Sau</span></li> <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>
</div>
<style>
    .product-card:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15)!important; transition: transform .2s ease-in-out, box-shadow .2s ease-in-out; }
    .product-image { border-bottom: 1px solid #eee; }
</style>