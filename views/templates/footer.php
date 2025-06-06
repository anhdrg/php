<?php
// /views/templates/footer.php
if (!defined('BASE_URL')) {
    require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
    require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
}
?>
</div> <footer class="bg-light text-center text-lg-start mt-auto">
    <div class="container p-4">
        <div class="row">
            <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                <h5 class="text-uppercase"><?php echo SITE_NAME; ?></h5>
                <p>
                    Chuyên cung cấp các tài khoản game Bulu Monster chất lượng cao với giá cả phải chăng.
                    Giao dịch an toàn, hỗ trợ nhanh chóng.
                </p>
            </div>

            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Liên kết</h5>
                <ul class="list-unstyled mb-0">
                    <li><a href="<?php echo base_url('page/about-us'); ?>" class="text-dark">Về chúng tôi</a></li>
                    <li><a href="<?php echo base_url('page/terms-of-service'); ?>" class="text-dark">Điều khoản dịch vụ</a></li>
                    <li><a href="<?php echo base_url('page/privacy-policy'); ?>" class="text-dark">Chính sách bảo mật</a></li>
                    <li><a href="<?php echo base_url('page/contact'); ?>" class="text-dark">Liên hệ</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Hỗ trợ</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo base_url('faq'); ?>" class="text-dark">Câu hỏi thường gặp (FAQ)</a></li>
                    <li><a href="mailto:support@example.com" class="text-dark">support@example.com</a></li>
                    </ul>
            </div>
        </div>
    </div>

    <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
        © <?php echo date("Y"); ?> Copyright:
        <a class="text-dark" href="<?php echo base_url(); ?>"><?php echo SITE_NAME; ?></a>. All Rights Reserved.
    </div>
</footer>

<script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/custom.js'); ?>"></script>

<?php
// Placeholder cho các script JS cụ thể của từng trang (nếu cần)
if (isset($page_specific_js) && !empty($page_specific_js)) {
    foreach ($page_specific_js as $script_path) {
        echo '<script src="' . asset_url($script_path) . '"></script>' . "\n";
    }
}
?>

</body>
</html>
