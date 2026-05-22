<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'CodeForge-Engine'; ?></title>
    <!-- Assets are loaded from root since we are routed from the entry point -->
    <link rel="stylesheet" href="assets/css/forge-ui.css">
</head>
<body>
    <!-- Sticky Glassmorphic Navbar -->
    <nav class="forge-navbar">
        <div class="forge-container forge-navbar-inner">
            <a href="index.php" class="forge-brand">
                <span>⚡</span> CodeForge-Engine
            </a>
            <div class="forge-nav-links">
                <a href="#" class="forge-nav-link active">Developer Dashboard</a>
                <a href="#ui-components" class="forge-nav-link">UI Elements</a>
                <a href="api/info" class="forge-nav-link" target="_blank">JSON API</a>
            </div>
        </div>
    </nav>

    <!-- Content Slot injection -->
    <main>
        <?php echo $content; ?>
    </main>

    <!-- Global Client JS -->
    <script src="assets/js/forge-core.js"></script>
</body>
</html>
