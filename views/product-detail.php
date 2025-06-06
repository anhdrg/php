<?php
// /views/product-detail.php
$product = $data['product'] ?? null;
$gallery_images = $product['gallery_images'] ?? [];

// Các biến SEO $page_title, $meta_description,... đã được controller đặt
?>

<div class="container mt-5 product-detail-page">
    <?php if ($product): ?>
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card shadow-sm">
                    <div class="card-body p-2 text-center">
                        <?php
                        $main_image_path = !empty($product['image_url']) ? asset_url($product['image_url']) : asset_url('images/placeholder_large.jpg');
                        ?>
                        <img src="<?php echo $main_image_path; ?>" 
                             class="img-fluid rounded product-main-image-display" 
                             alt="<?php echo htmlspecialchars($product['name'] ?? 'Hình ảnh sản phẩm'); ?>" 
                             style="max-height: 400px; width: auto; object-fit: contain; display: inline-block; margin-bottom: 15px; cursor: pointer;"
                             id="mainProductImage"
                             data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                             onclick="updateZoomImage('<?php echo $main_image_path; ?>', '<?php echo htmlspecialchars($product['name'] ?? 'Ảnh chính'); ?>')">
                        
                        <?php if (!empty($gallery_images)): ?>
                            <hr>
                            <h6 class="text-start mb-2 ms-1">Bộ sưu tập ảnh:</h6>
                            <div class="row g-2 justify-content-center product-gallery-thumbnails">
                                <?php if (!empty($product['image_url'])): ?>
                                <div class="col-auto">
                                    <img src="<?php echo $main_image_path; ?>" 
                                         class="img-thumbnail gallery-thumbnail active" 
                                         alt="Ảnh đại diện - <?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                                         style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;"
                                         onclick="changeMainImage('<?php echo $main_image_path; ?>', '<?php echo htmlspecialchars($product['name'] ?? 'Ảnh chính'); ?>', this)">
                                </div>
                                <?php endif; ?>

                                <?php foreach ($gallery_images as $gallery_image): ?>
                                    <?php 
                                    $gallery_item_path = asset_url($gallery_image['file_path']);
                                    $gallery_alt_text = htmlspecialchars($gallery_image['alt_text_override'] ?? ($gallery_image['alt_text_default'] ?? ('Ảnh chi tiết - ' . ($product['name'] ?? ''))));
                                    ?>
                                    <div class="col-auto">
                                        <img src="<?php echo $gallery_item_path; ?>" 
                                             class="img-thumbnail gallery-thumbnail" 
                                             alt="<?php echo $gallery_alt_text; ?>" 
                                             style="width: 70px; height: 70px; object-fit: cover; cursor: pointer;"
                                             onclick="changeMainImage('<?php echo $gallery_item_path; ?>', '<?php echo $gallery_alt_text; ?>', this)"
                                             data-bs-toggle="modal" data-bs-target="#imageZoomModal"
                                             data-zoom-src="<?php echo $gallery_item_path; ?>" 
                                             data-zoom-alt="<?php echo $gallery_alt_text; ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo base_url(); ?>">Trang chủ</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo base_url('product/list'); ?>">Sản phẩm</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name'] ?? 'Chi tiết sản phẩm'); ?></li>
                    </ol>
                </nav>

                <h1 class="product-title mb-3"><?php echo htmlspecialchars($product['name'] ?? 'N/A'); ?></h1>
                <p class="product-price fs-3 fw-bold text-danger mb-3"><?php echo format_currency($product['price'] ?? 0); ?></p>

                <div class="product-stock mb-3">
                    <?php if (isset($product['available_stock']) && $product['available_stock'] > 0): ?>
                        <span class="badge bg-success fs-6"><i class="fas fa-check-circle"></i> Còn hàng (<?php echo $product['available_stock']; ?> mã)</span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6"><i class="fas fa-times-circle"></i> Hết hàng</span>
                    <?php endif; ?>
                </div>

                <div class="product-actions mb-4">
                    <?php if (isset($product['available_stock']) && $product['available_stock'] > 0): ?>
                        <form action="<?php echo base_url('checkout/direct_buy'); ?>" method="POST" id="buy-now-form" class="d-inline-block me-2">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-shopping-bag"></i> Mua Ngay
                            </button>
                        </form>
                    <?php else: ?>
                         <button type="button" class="btn btn-secondary btn-lg" disabled><i class="fas fa-shopping-bag"></i> Hết hàng</button>
                    <?php endif; ?>

                    <?php // Nút Mở Link Video (nếu có video_url) ?>
                    <?php if (!empty($product['video_url'])): ?>
                         <a href="<?php echo htmlspecialchars($product['video_url']); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-lg">
                            <i class="fas fa-play-circle"></i> Xem Video Review
                        </a>
                    <?php endif; ?>
                </div>
                
                <hr>
                <div class="product-description mt-4">
                    <h4 class="mb-3">Mô tả loại tài khoản</h4>
                    <div><?php echo !empty($product['description']) ? nl2br(htmlspecialchars($product['description'])) : '<em>Chưa có mô tả chi tiết cho loại tài khoản này.</em>'; ?></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="imageZoomModal" tabindex="-1" aria-labelledby="imageZoomModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="imageZoomModalLabel">Xem ảnh chi tiết</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" id="zoomedProductImage" class="img-fluid" alt="Ảnh phóng to">
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger text-center" role="alert">
            Sản phẩm bạn tìm kiếm không tồn tại hoặc không có sẵn.
            <a href="<?php echo base_url('product/list'); ?>" class="btn btn-primary mt-2">Quay lại danh sách sản phẩm</a>
        </div>
    <?php endif; ?>
</div>

<style>
    .product-main-image-display { border: 1px solid #ddd; transition: opacity 0.3s ease-in-out; }
    .product-title { font-weight: 500; }
    .gallery-thumbnail { border: 2px solid transparent; transition: border-color 0.2s ease-in-out; margin: 2px; }
    .gallery-thumbnail.active { border-color: #0d6efd; }
    .gallery-thumbnail:hover { border-color: #999; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const mainProductImage = document.getElementById('mainProductImage');
    const galleryThumbnails = document.querySelectorAll('.gallery-thumbnail');
    const zoomedProductImage = document.getElementById('zoomedProductImage');
    const imageZoomModalLabel = document.getElementById('imageZoomModalLabel');

    window.changeMainImage = function(newSrc, newAlt, clickedThumbnail) {
        if (mainProductImage) {
            mainProductImage.src = newSrc;
            mainProductImage.alt = newAlt;
            // Cập nhật data-zoom-src cho ảnh chính nếu nó cũng là trigger cho modal
            mainProductImage.setAttribute('data-zoom-src', newSrc); 
            mainProductImage.setAttribute('data-zoom-alt', newAlt);
        }
        galleryThumbnails.forEach(thumb => thumb.classList.remove('active'));
        if (clickedThumbnail) {
            clickedThumbnail.classList.add('active');
        }
    }

    window.updateZoomImage = function(src, alt) {
        if (zoomedProductImage) {
            zoomedProductImage.src = src;
            zoomedProductImage.alt = alt;
        }
        if (imageZoomModalLabel) {
            imageZoomModalLabel.textContent = alt || "Xem ảnh chi tiết";
        }
    }

    if (mainProductImage) {
        mainProductImage.addEventListener('click', function() {
            // Lấy src và alt hiện tại của ảnh chính để zoom
            updateZoomImage(this.src, this.alt);
        });
    }

    galleryThumbnails.forEach(thumb => {
        thumb.addEventListener('click', function(event) {
            // Hàm changeMainImage đã được gọi qua onclick attribute
            // Ở đây chúng ta cũng cập nhật ảnh cho modal zoom khi thumbnail được click
            // nếu người dùng click trực tiếp vào thumbnail để zoom (do thumbnail cũng có data-bs-toggle)
            if (event.target.closest('.gallery-thumbnail').dataset.bsTarget === '#imageZoomModal') {
                 updateZoomImage(this.dataset.zoomSrc || this.src, this.dataset.zoomAlt || this.alt);
            }
        });
    });
});
</script>