<?php
// /admin/manage-game-codes.php
// Dữ liệu được truyền từ GameCodeController::index():
// $data['gameCodes'], $data['currentPage'], $data['totalPages'], $data['totalCodes']
// $data['filters'], $data['allProductTypes'], $data['generated_codes_info']
// $admin_page_title và $admin_current_page_title đã được controller (thông qua AdminBaseController) đặt.

$filters = $data['filters'] ?? [];
$allProductTypes = $data['allProductTypes'] ?? [];
$generated_codes_info = $data['generated_codes_info'] ?? null; 

$game_code_statuses = [
    'available' => 'Có sẵn', 'sold' => 'Đã bán',
    'reserved' => 'Đang giữ', 'disabled' => 'Vô hiệu hóa'
];
?>

<div class="container-fluid">
    <?php echo display_flash_message('admin_recovery_success'); ?>
    <?php echo display_flash_message('admin_recovery_error'); ?>
    <?php echo display_flash_message('admin_recovery_warning'); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Thêm Mã Game Mới Vào Kho</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('admin/game-code/generate'); ?>" method="POST" class="row g-3 align-items-start">
                <div class="col-md-4">
                    <label for="product_id_generate" class="form-label form-label-sm">Chọn Loại Sản Phẩm <span class="text-danger">*</span></label>
                    <select name="product_id_generate" id="product_id_generate" class="form-select form-select-sm <?php echo !empty($filters['product_id_generate_error']) ? 'is-invalid' : ''; ?>" required>
                        <option value="">-- Chọn loại sản phẩm --</option>
                        <?php if (!empty($allProductTypes)): ?>
                            <?php foreach ($allProductTypes as $productType): ?>
                                <option value="<?php echo $productType['id']; ?>" <?php echo (isset($filters['product_id']) && $filters['product_id'] == $productType['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($productType['name']); ?> (ID: <?php echo $productType['id']; ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                     <?php if (!empty($filters['product_id_generate_error'])): ?><div class="invalid-feedback"><?php echo $filters['product_id_generate_error']; ?></div><?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label for="code_value_input" class="form-label form-label-sm">Nội dung Mã Game / Thông tin Tài khoản <span class="text-danger">*</span></label>
                    <textarea name="code_value_input" id="code_value_input" class="form-control form-control-sm <?php echo !empty($filters['code_value_input_error']) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Nhập mỗi mã trên một dòng." required></textarea>
                    <div class="form-text">Mỗi dòng sẽ được coi là một mã riêng biệt.</div>
                    <?php if (!empty($filters['code_value_input_error'])): ?><div class="invalid-feedback"><?php echo $filters['code_value_input_error']; ?></div><?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="notes_input" class="form-label form-label-sm">Ghi chú của Admin (Tùy chọn)</label>
                    <input type="text" name="notes_input" id="notes_input" class="form-control form-control-sm" placeholder="Ghi chú thêm về lô mã này">
                </div>
                <div class="col-12 text-end mt-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Thêm Mã Vào Kho
                    </button>
                </div>
            </form>
            <?php if ($generated_codes_info !== null): ?>
            <div class="alert alert-info mt-3">
                <h5 class="alert-heading">Thông tin về các mã game vừa xử lý:</h5>
                <?php if (is_array($generated_codes_info)): ?>
                    <p>Các mã sau đã được thêm (hoặc ghi nhận thành công):</p>
                    <ul class="list-unstyled mb-0 small" style="max-height: 150px; overflow-y: auto;">
                        <?php foreach ($generated_codes_info as $code_item): ?>
                            <li><code><?php echo htmlspecialchars(is_array($code_item) ? ($code_item['code_value'] ?? json_encode($code_item)) : $code_item); ?></code></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p><?php echo htmlspecialchars($generated_codes_info); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Kho Mã Game / Tài Khoản (Tổng: <?php echo $data['totalCodes'] ?? 0; ?>)</h6>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('admin/game-code'); // Form filter trỏ về action index của GameCodeController ?>" method="GET" class="mb-4 p-3 border rounded bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="product_id_filter_list" class="form-label form-label-sm">Lọc theo Loại Sản Phẩm</label>
                        <select name="product_id_filter" id="product_id_filter_list" class="form-select form-select-sm">
                            <option value="">-- Tất cả loại SP --</option>
                            <?php if (!empty($allProductTypes)): foreach ($allProductTypes as $productType): ?>
                                <option value="<?php echo $productType['id']; ?>" <?php echo (isset($filters['product_id']) && $filters['product_id'] == $productType['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($productType['name']); ?>
                                </option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="status_filter" class="form-label form-label-sm">Trạng thái Mã</label>
                        <select name="status_filter" id="status_filter" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                            <?php foreach ($game_code_statuses as $key => $value): ?>
                                <option value="<?php echo $key; ?>" <?php echo (isset($filters['status']) && $filters['status'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="search_code_value_filter" class="form-label form-label-sm">Tìm trong nội dung mã</label>
                        <input type="text" name="search_code_value_filter" id="search_code_value_filter" class="form-control form-control-sm" placeholder="Nhập một phần mã/user/pass..." value="<?php echo htmlspecialchars($filters['search_code_value'] ?? ''); ?>">
                    </div>
                     <div class="col-md-2">
                        <label for="buyer_username_filter" class="form-label form-label-sm">Người mua (Username)</label>
                        <input type="text" name="buyer_username_filter" id="buyer_username_filter" class="form-control form-control-sm" placeholder="Username người mua" value="<?php echo htmlspecialchars($filters['buyer_username_search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-sm w-100">Lọc Mã</button>
                        <?php if (!empty($filters)): ?>
                        <a href="<?php echo base_url('admin/game-code'); // Link xóa filter cũng trỏ về action index của GameCodeController ?>" class="btn btn-outline-secondary btn-sm w-100 mt-1" title="Xóa bộ lọc">
                            <i class="fas fa-times"></i> Xóa Lọc
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (!empty($data['gameCodes'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID Mã</th> <th>Loại Sản Phẩm</th> <th>Nội dung (1 phần)</th> <th class="text-center">Trạng thái</th>
                                <th>Người mua</th> <th>Giao dịch #</th> <th>Ngày bán</th> <th>Ngày tạo</th> <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['gameCodes'] as $code): ?>
                                <tr class="<?php echo $code['status'] == 'sold' ? 'table-success' : ($code['status'] == 'disabled' ? 'table-secondary text-muted' : ($code['status'] == 'reserved' ? 'table-warning' : '')); ?>">
                                    <td><?php echo $code['id']; ?></td>
                                    <td><a href="<?php echo base_url('admin/product/edit/' . $code['product_id']); ?>"><?php echo htmlspecialchars($code['product_name'] ?? 'N/A'); ?></a></td>
                                    <td><span title="<?php echo htmlspecialchars($code['code_value']); ?>"><?php echo htmlspecialchars(create_excerpt($code['code_value'], 40)); ?></span></td>
                                    <td class="text-center">
                                        <?php 
                                        $status_text = $game_code_statuses[$code['status']] ?? ucfirst($code['status']);
                                        $status_badge = 'secondary';
                                        if ($code['status'] === 'available') $status_badge = 'primary';
                                        else if ($code['status'] === 'sold') $status_badge = 'success';
                                        else if ($code['status'] === 'reserved') $status_badge = 'warning';
                                        else if ($code['status'] === 'disabled') $status_badge = 'light text-dark border';
                                        ?>
                                        <span class="badge bg-<?php echo $status_badge; ?>"><?php echo htmlspecialchars($status_text); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($code['buyer_username'] ?? 'N/A'); ?></td>
                                    <td><?php if(!empty($code['transaction_id'])):?><a href="<?php echo base_url('admin/transaction/view/' . $code['transaction_id']); ?>">#<?php echo $code['transaction_id']; ?></a><?php else: echo 'N/A'; endif; ?></td>
                                    <td><?php echo $code['sold_at'] ? date('d/m/Y H:i', strtotime($code['sold_at'])) : 'N/A'; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($code['created_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="<?php echo base_url('admin/game-code/edit/' . $code['id']); ?>" class="btn btn-warning btn-sm" title="Sửa mã game này"><i class="fas fa-edit"></i></a>
                                        <?php if ($code['status'] !== 'sold'): ?>
                                        <form action="<?php echo base_url('admin/game-code/delete'); ?>" method="POST" class="d-inline delete-code-form ms-1">
                                            <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa mã này" onclick="return confirm('Xóa Mã Game ID: <?php echo $code['id']; ?>?');"><i class="fas fa-trash-alt"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (($data['totalPages'] ?? 0) > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4"> <ul class="pagination justify-content-center">
                        <?php /* ... Logic phân trang như cũ, đảm bảo dùng đúng base_url('admin/game-code') và các filter params ... */ 
                        $currentUrlParams = $filters; 
                        if (isset($_GET['sort'])) $currentUrlParams['sort'] = $_GET['sort']; if (isset($_GET['dir'])) $currentUrlParams['dir'] = $_GET['dir'];
                        if (($data['currentPage'] ?? 1) > 1): $currentUrlParams['page'] = ($data['currentPage'] ?? 1) - 1; ?> <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Trước</a></li>
                        <?php else: ?> <li class="page-item disabled"><span class="page-link">Trước</span></li> <?php endif; 
                        $num_links = 2; $start = max(1, ($data['currentPage'] ?? 1) - $num_links); $end = min(($data['totalPages'] ?? 1), ($data['currentPage'] ?? 1) + $num_links);
                        if ($start > 1): $currentUrlParams['page'] = 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
                        for ($i = $start; $i <= $end; $i++): $currentUrlParams['page'] = $i; ?> <li class="page-item <?php echo ($i == ($data['currentPage'] ?? 1) ? 'active' : ''); ?>"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo $i; ?></a></li> <?php endfor;
                        if ($end < ($data['totalPages'] ?? 1)): if ($end < ($data['totalPages'] ?? 1) - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; $currentUrlParams['page'] = ($data['totalPages'] ?? 1); ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo ($data['totalPages'] ?? 1); ?></a></li><?php endif; ?>
                        <?php if (($data['currentPage'] ?? 1) < ($data['totalPages'] ?? 1)): $currentUrlParams['page'] = ($data['currentPage'] ?? 1) + 1; ?> <li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Sau</a></li>
                        <?php else: ?> <li class="page-item disabled"><span class="page-link">Sau</span></li> <?php endif; ?>
                    </ul> </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">Không tìm thấy mã game/tài khoản nào. <a href="<?php echo base_url('admin/game-code' . (!empty($filters['product_id']) ? '?product_id_filter='.$filters['product_id'] : '')); ?>">Thử lại</a> hoặc xóa bộ lọc.</div>
            <?php endif; ?>
        </div>
    </div>
</div>