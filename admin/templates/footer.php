<?php
// /admin/templates/footer.php
?>
        </main> </div> </div> <script src="<?php echo asset_url('js/bootstrap.bundle.min.js'); ?>"></script>
<script src="<?php echo asset_url('js/admin-custom.js'); ?>"></script> 
<?php
// Placeholder cho các script JS cụ thể của từng trang admin (nếu cần)
if (isset($page_specific_admin_js) && !empty($page_specific_admin_js)) {
    foreach ($page_specific_admin_js as $script_path) {
        echo '<script src="' . asset_url($script_path) . '"></script>' . "\n";
    }
}
?>
</body>
</html>
