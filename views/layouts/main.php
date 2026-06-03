<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'VSItoA - Crypto Earning Platform' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= Config::get('app.base_path') ?>/assets/css/style.css" rel="stylesheet">
    <link href="<?= Config::get('app.base_path') ?>/assets/css/verified-badge.css" rel="stylesheet">
    <?php
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '';
    $basePath = (string) Config::get('app.base_path', '');
    $normalized = $path;
    if ($basePath !== '' && $basePath !== '/' && str_starts_with($normalized, $basePath)) {
        $normalized = substr($normalized, strlen($basePath));
    }
    $isAdminArea = str_starts_with($normalized ?: '/', '/admin');
    ?>
    <?php if ($isAdminArea): ?>
        <link href="<?= Config::get('app.base_path') ?>/assets/css/admin.css" rel="stylesheet">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= Config::get('app.base_path') ?>/assets/images/favicon.ico">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?= $_SESSION['_token'] ?? '' ?>">
    <script>
        window.VSItoA_BASE_PATH = <?= json_encode((string) Config::get('app.base_path', '')) ?>;
    </script>
</head>
<body class="<?= $isAdminArea ? 'admin-body' : '' ?>">

    <!-- Navigation -->
    <?php if (isset($show_navbar) && $show_navbar): ?>
        <?php if ($isAdminArea): ?>
            <?php include ROOT_PATH . '/views/partials/admin_navbar.php'; ?>
        <?php else: ?>
            <?php include ROOT_PATH . '/views/partials/navbar.php'; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Main Content -->
    <main>
        <?php if (isset($show_sidebar) && $show_sidebar): ?>
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-3 col-lg-2 sidebar">
                        <?php if ($isAdminArea): ?>
                            <?php include ROOT_PATH . '/views/partials/admin_sidebar.php'; ?>
                        <?php else: ?>
                            <?php include ROOT_PATH . '/views/partials/sidebar.php'; ?>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9 col-lg-10 main-content">
                        <?= $content ?? '' ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?= $content ?? '' ?>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php if (isset($show_footer) && $show_footer): ?>
        <?php include ROOT_PATH . '/views/partials/footer.php'; ?>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Custom JS -->
    <?php if (!$isAdminArea): ?>
        <script src="<?= Config::get('app.base_path') ?>/assets/js/app.js"></script>
    <?php endif; ?>
    
    <!-- Page-specific scripts -->
    <?= $scripts ?? '' ?>
</body>
</html>
