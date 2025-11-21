<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>本草平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">本草平台</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class=" navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="herb_list.php">本草库</a></li>
                <li class="nav-item"><a class="nav-link" href="community.php">互动社区</a></li>
                <?php if(isset($_SESSION['user'])): ?>
                    <?php if($_SESSION['user']['user_type'] === 'admin'): ?>
                        <!-- TODO: 管理员后台功能 -->
                        <li class="nav-item"><a class="nav-link" href="admin.php">管理后台</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <div class="ms-auto d-flex align-items-center">
                <?php if(isset($_SESSION['user'])): ?>
                    <span class="text-white me-3">欢迎，<?php echo htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <!-- TODO：添加个人中心链接 -->
                    <a class="btn btn-outline-light btn me-2" href="user_profile.php">个人中心</a>
                    <a class="btn btn-outline-light btn" href="logout.php">退出</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn me-2" href="login.php">登录</a>
                    <a class="btn btn-light btn" href="register.php">注册</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- 页面内容插入点 -->
<div class="container" id="main-content">
    <?php echo isset($pageContent) ? $pageContent : ''; ?>
</div>

<footer class="bg-light text-center text-muted py-4 mt-5 border-top">
    <div class="container">
        <div>本草植物综合服务平台 Copyright&copy; 2025 | NgaiYeanCoi</div>
        <div class="mt-2">
            <a href="community.php" class="me-3">互动社区</a>
            <a href="herb_list.php">本草库</a>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>