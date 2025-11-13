<?php
session_start();
require_once 'config/db.php';

// 2. Lấy SLUG gói từ URL
$package_slug = isset($_GET['id']) ? $_GET['id'] : '';

// 3. Lấy thông tin gói
$stmt = $conn->prepare("SELECT * FROM package WHERE slug = ? AND active = 1");
$stmt->bind_param("s", $package_slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    include 'templates/header.php';
    echo '<div class="container my-5"><div class="alert alert-danger">Lỗi: Gói dịch vụ không tồn tại.</div></div>';
    include 'templates/footer.php';
    exit;
}

$package = $result->fetch_assoc();
$packageName = $package['name'];
$package_db_id = $package['id'];
$features = explode("\n", trim($package['description']));
$stmt->close();

// 5. Lấy dữ liệu Portfolio
$portfolio = [];
$stmt_p = $conn->prepare("SELECT * FROM portfolio WHERE package_id = ?");
$stmt_p->bind_param("i", $package_db_id);
$stmt_p->execute();
$portfolio_result = $stmt_p->get_result();
while ($row = $portfolio_result->fetch_assoc()) {
    $portfolio[] = $row;
}
$stmt_p->close();

include 'templates/header.php';
?>

<div class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="services.php">Bảng giá</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($packageName); ?></li>
        </ol>
    </nav>
</div>

<div class="container">
    <h1 class="display-4 fw-bold mb-4 package-title"><?php echo htmlspecialchars($package['name']); ?></h1>
</div>

<div class="container">
    <div class="overview-box p-4 p-md-5 rounded">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <h3 class="fw-bold text-dark-blue">Tổng quan</h3>
                <p class="text-muted"><?php echo htmlspecialchars($package['overview']); ?></p>
            </div>
            <div class="col-lg-5">
                <ul class="list-unstyled checklist">
                    <?php foreach ($features as $feature): ?>
                    <li>
                        <i class="bi bi-check-lg text-primary"></i>
                        <span><?php echo htmlspecialchars(ltrim(trim($feature), '- ')); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="container my-5 py-4">
    <h2 class="text-center fw-bold mb-5">Một số dự án đã thực hiện</h2>

    <?php if (!empty($portfolio)): ?>
    <div class="row g-4">
        <?php foreach ($portfolio as $project): ?>
        <div class="col-md-4">
            <div class="card portfolio-card h-100">
                <img src="<?php echo htmlspecialchars($project['image_url']); ?>" class="card-img-top"
                    alt="<?php echo htmlspecialchars($project['title']); ?>" style="height: 200px; object-fit: cover;">

                <div class="card-body">
                    <h5 class="card-title fw-bold"><?php echo htmlspecialchars($project['title']); ?></h5>
                    <p class="card-text text-muted small"><?php echo htmlspecialchars($project['description']); ?></p>
                </div>

                <div class="card-footer bg-transparent border-0 pb-3">
                    <button type="button"
                        class="btn <?php echo $project['type'] == 'pdf' ? 'btn-outline-dark' : 'btn-schedio-primary'; ?> w-100 btn-view-project"
                        data-title="<?php echo htmlspecialchars($project['title']); ?>"
                        data-desc="<?php echo htmlspecialchars($project['description']); ?>"
                        data-img="<?php echo htmlspecialchars($project['image_url']); ?>"
                        data-link="<?php echo htmlspecialchars($project['link_url']); ?>"
                        data-type="<?php echo htmlspecialchars($project['type']); ?>">
                        <?php echo $project['type'] == 'pdf' ? '<i class="bi bi-file-pdf"></i> Xem báo cáo' : '<i class="bi bi-eye"></i> Xem chi tiết'; ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="text-center text-muted py-5 bg-light rounded">
        <i class="bi bi-folder2-open display-4 mb-3 d-block"></i>
        <p>Chưa có dự án mẫu nào cho gói này.</p>
    </div>
    <?php endif; ?>
</div>

<div class="container my-5 text-center">
    <a href="services.php" class="btn btn-outline-primary btn-lg px-5 rounded-pill">
        <i class="bi bi-arrow-left me-2"></i> Quay lại Bảng giá
    </a>
</div>

<div class="modal fade" id="projectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title fw-bold text-dark-blue" id="modalProjectTitle">Tiêu đề dự án</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center pt-0">
                <div class="mb-4 bg-light rounded overflow-hidden">
                    <img src="" id="modalProjectImg" class="img-fluid" alt="Project Image" style="max-height: 500px;">
                </div>

                <p id="modalProjectDesc" class="text-muted fst-italic mb-4"></p>

                <a href="#" id="modalProjectLink" target="_blank"
                    class="btn btn-primary px-5 py-2 rounded-pill shadow-sm">
                    <i class="bi bi-box-arrow-up-right me-2"></i> Truy cập bài viết gốc / File PDF
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const projectModal = new bootstrap.Modal(document.getElementById('projectModal'));
    const buttons = document.querySelectorAll('.btn-view-project');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            // 1. Lấy dữ liệu từ data-attribute của nút bấm
            const title = this.getAttribute('data-title');
            const desc = this.getAttribute('data-desc');
            const img = this.getAttribute('data-img');
            const link = this.getAttribute('data-link');
            const type = this.getAttribute('data-type');

            // 2. Điền dữ liệu vào Modal
            document.getElementById('modalProjectTitle').innerText = title;
            document.getElementById('modalProjectDesc').innerText = desc;
            document.getElementById('modalProjectImg').src = img;

            const linkBtn = document.getElementById('modalProjectLink');
            linkBtn.href = link;

            // Đổi text nút dựa trên loại file
            if (type === 'pdf') {
                linkBtn.innerHTML =
                    '<i class="bi bi-file-earmark-pdf-fill me-2"></i> Mở file PDF';
                linkBtn.classList.remove('btn-primary');
                linkBtn.classList.add('btn-danger');
            } else {
                linkBtn.innerHTML = '<i class="bi bi-facebook me-2"></i> Xem bài viết gốc';
                linkBtn.classList.remove('btn-danger');
                linkBtn.classList.add('btn-primary');
            }

            // 3. Hiện Modal
            projectModal.show();
        });
    });
});
</script>