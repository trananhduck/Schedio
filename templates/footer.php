<?php
if (!isset($base_url)) {
    $base_url = '/booking-website';
}
?>

</main>

<footer class="py-5 schedio-footer">
    <div class="container">
        <div class="row">
            <div class="col-lg-5 col-md-12 mb-4">
                <h5 class="fw-bold fs-3 mb-3">Schedio</h5>
                <p class="text-muted">Email: support@schedio.vn</p>
                <p class="text-muted">Hotline: (084) 123 456 789</p>
                <div>
                    <a href="#" class="text-muted fs-4 me-3"><i class="bi bi-tiktok"></i></a>
                    <a href="#" class="text-muted fs-4"><i class="bi bi-facebook"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <h6 class="fw-bold text-uppercase mb-3">Về Schedio</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="<?php echo $base_url; ?>/about.php" class="text-muted text-decoration-none">Giới
                            thiệu</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo $base_url; ?>/services.php" class="text-muted text-decoration-none">Bảng
                            giá</a>
                    </li>
                    <li class="mb-2">
                        <a href="<?php echo $base_url; ?>/contact.php" class="text-muted text-decoration-none">Liên
                            hệ</a>
                    </li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <h6 class="fw-bold text-uppercase mb-3">Các kênh truyền thông</h6>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="https://www.facebook.com/grabfanthang9" class="text-muted text-decoration-none">Page
                            Grab Fan Tháng 9</a>
                    </li>
                    <li class="mb-2">
                        <a href="https://www.facebook.com/rapfanthamthinh" class="text-muted text-decoration-none">Page
                            Rap Fan Thám Thính</a>
                    </li>
                    <li class="mb-2">
                        <a href="https://www.tiktok.com/@grabfanthang9" class="text-muted text-decoration-none">TikTok
                            Grab Fan Tháng 9</a>
                    </li>
                    <li class="mb-2">
                        <a href="https://www.facebook.com/groups/8276007849195211"
                            class="text-muted text-decoration-none">Group Cộng đồng Grab Việt Underground</a>
                    </li>
                </ul>
            </div>
        </div>

        <hr>
        <div class="text-center text-muted small">
            &copy; <?php echo date('Y'); ?> Schedio. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
</script>
</body>

</html>