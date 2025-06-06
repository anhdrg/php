<?php
// /admin/manage-password-reset-tokens.php
// Dữ liệu: $data['tokens'], $data['currentPage'], $data['totalPages'], $data['totalTokens']
// $data['filters'], $data['allUsers']

$filters = $data['filters'] ?? [];
$allUsers = $data['allUsers'] ?? [];
?>

<div class="container-fluid">
    <?php echo display_flash_message('admin_token_success'); ?>
    <?php echo display_flash_message('admin_token_error'); ?>
    <?php echo display_flash_message('admin_token_info'); ?>


    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Danh Sách Token Reset Mật Khẩu (Tổng: <?php echo $data['totalTokens'] ?? 0; ?>)</h6>
            <form action="<?php echo base_url('admin/password-reset-token/cleanup'); ?>" method="POST" class="d-inline" 
                  onsubmit="return confirm('Bạn có chắc chắn muốn xóa tất cả các token đã hết hạn và đã sử dụng không?');">
                <input type="hidden" name="confirm_cleanup" value="1">
                <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="fas fa-broom"></i> Dọn dẹp Token Cũ
                </button>
            </form>
        </div>
        <div class="card-body">
            <form action="<?php echo base_url('admin/password-reset-token'); ?>" method="GET" class="mb-4 p-3 border rounded bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="username_search_filter" class="form-label form-label-sm">Tìm theo Username</label>
                        <input type="text" name="username_search_filter" id="username_search_filter" class="form-control form-control-sm" placeholder="Nhập username..." value="<?php echo htmlspecialchars($filters['username_search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="selector_search_filter" class="form-label form-label-sm">Tìm theo Selector</label>
                        <input type="text" name="selector_search_filter" id="selector_search_filter" class="form-control form-control-sm" placeholder="Nhập selector..." value="<?php echo htmlspecialchars($filters['selector_search'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="user_id_filter_list" class="form-label form-label-sm">Lọc theo User ID</label>
                        <input type="number" name="user_id_filter" id="user_id_filter_list" class="form-control form-control-sm" placeholder="ID người dùng" value="<?php echo htmlspecialchars($filters['user_id'] ?? ''); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="is_used_filter" class="form-label form-label-sm">Trạng thái sử dụng</label>
                        <select name="is_used_filter" id="is_used_filter" class="form-select form-select-sm">
                            <option value="">-- Tất cả --</option>
                            <option value="0" <?php echo (isset($filters['is_used']) && $filters['is_used'] == '0') ? 'selected' : ''; ?>>Chưa sử dụng</option>
                            <option value="1" <?php echo (isset($filters['is_used']) && $filters['is_used'] == '1') ? 'selected' : ''; ?>>Đã sử dụng</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-info btn-sm w-100">Lọc Token</button>
                        <?php if (!empty($filters)): ?>
                        <a href="<?php echo base_url('admin/password-reset-token'); ?>" class="btn btn-outline-secondary btn-sm w-100 mt-1" title="Xóa bộ lọc">
                            <i class="fas fa-times"></i> Xóa Lọc
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <?php if (!empty($data['tokens'])): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID Token</th>
                                <th>Người dùng</th>
                                <th>Selector</th>
                                <th class="text-center">Đã sử dụng</th>
                                <th>Ngày tạo</th>
                                <th>Ngày hết hạn</th>
                                <th class="text-center">Hành Động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['tokens'] as $token): ?>
                                <tr class="<?php echo $token['is_used'] ? 'table-secondary text-muted' : (strtotime($token['expires_at']) < time() ? 'table-warning' : ''); ?>">
                                    <td><?php echo $token['id']; ?></td>
                                    <td>
                                        <a href="<?php echo base_url('admin/user-edit/' . $token['user_id']); ?>">
                                            <?php echo htmlspecialchars($token['user_username'] ?? 'N/A'); ?>
                                        </a> (ID: <?php echo $token['user_id']; ?>)
                                    </td>
                                    <td><code><?php echo htmlspecialchars($token['selector']); ?></code></td>
                                    <td class="text-center">
                                        <?php if ($token['is_used']): ?>
                                            <span class="badge bg-success">Đã dùng</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Chưa dùng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($token['created_at'])); ?></td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($token['expires_at'])); ?>
                                        <?php if (strtotime($token['expires_at']) < time() && !$token['is_used']): ?>
                                            <span class="badge bg-danger ms-1">Hết hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <form action="<?php echo base_url('admin/password-reset-token/delete'); ?>" method="POST" class="d-inline delete-token-form">
                                            <input type="hidden" name="token_id" value="<?php echo $token['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Xóa token này"
                                                    onclick="return confirm('Bạn có chắc chắn muốn xóa token ID: <?php echo $token['id']; ?> của người dùng \'<?php echo htmlspecialchars(addslashes($token['user_username'] ?? '')); ?>\'?');">
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
                    <nav aria-label="Page navigation" class="mt-4"> <ul class="pagination justify-content-center">
                        <?php $currentUrlParams = $filters; /* ... (logic phân trang như các trang khác) ... */ ?>
                        <?php if ($data['currentPage'] > 1): $currentUrlParams['page'] = $data['currentPage'] - 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Trước</a></li><?php else: ?><li class="page-item disabled"><span class="page-link">Trước</span></li><?php endif; ?>
                        <?php $num_links = 2; $start = max(1, $data['currentPage'] - $num_links); $end = min($data['totalPages'], $data['currentPage'] + $num_links);
                        if ($start > 1): $currentUrlParams['page'] = 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">1</a></li><?php if ($start > 2): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; endif;
                        for ($i = $start; $i <= $end; $i++): $currentUrlParams['page'] = $i; ?><li class="page-item <?php echo ($i == $data['currentPage'] ? 'active' : ''); ?>"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo $i; ?></a></li><?php endfor;
                        if ($end < $data['totalPages']): if ($end < $data['totalPages'] - 1): ?><li class="page-item disabled"><span class="page-link">...</span></li><?php endif; $currentUrlParams['page'] = $data['totalPages']; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>"><?php echo $data['totalPages']; ?></a></li><?php endif; ?>
                        <?php if ($data['currentPage'] < $data['totalPages']): $currentUrlParams['page'] = $data['currentPage'] + 1; ?><li class="page-item"><a class="page-link" href="?<?php echo http_build_query($currentUrlParams); ?>">Sau</a></li><?php else: ?><li class="page-item disabled"><span class="page-link">Sau</span></li><?php endif; ?>
                    </ul> </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">Không tìm thấy token reset mật khẩu nào.</div>
            <?php endif; ?>
        </div>
    </div>
</div>