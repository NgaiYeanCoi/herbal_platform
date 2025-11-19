<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>本草植物综合服务平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero { background: #f0f7ee; padding: 4rem 0; }
        .user-type { margin: 2rem 0; }
    </style>
</head>
<body>
<!-- 导航栏 -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="index.php">本草平台</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">首页</a></li>
                <li class="nav-item"><a class="nav-link" href="herb_list.php">本草库</a></li>
                <li class="nav-item"><a class="nav-link" href="science.php">科普专区</a></li>
                <li class="nav-item"><a class="nav-link" href="community.php">互动社区</a></li>
            </ul>
        </div>
    </div>
</nav>

<!-- 搜索区 -->
<div class="hero text-center">
    <div class="container">
        <h1>探索中医药本草的智慧</h1>
        <p class="lead mt-3">查询植物功效、食疗配方、专业数据</p>
        <form action="herb_list.php" method="get" class="mt-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="keyword" class="form-control" placeholder="输入植物名称、功效..." required>
                        <button class="btn btn-success" type="submit">搜索</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 用户类型入口 -->
<div class="container user-type">
    <h2 class="text-center mb-4">按用户类型访问</h2>
    <div class="row">
        <div class="col-md-3 text-center">
            <div class="card p-3">
                <h5>专业学习者</h5>
                <p>专业数据库、3D模型</p>
                <a href="herb_list.php?type=professional" class="btn btn-outline-success">进入</a>
            </div>
        </div>
        <div class="col-md-3 text-center">
            <div class="card p-3">
                <h5>大众养生</h5>
                <p>食疗配方、家庭种植</p>
                <a href="herb_list.php?type=public" class="btn btn-outline-success">进入</a>
            </div>
        </div>
        <div class="col-md-3 text-center">
            <div class="card p-3">
                <h5>基层医生</h5>
                <p>诊疗指南、识别工具</p>
                <a href="herb_list.php?type=doctor" class="btn btn-outline-success">进入</a>
            </div>
        </div>
        <div class="col-md-3 text-center">
            <div class="card p-3">
                <h5>文化爱好者</h5>
                <p>古籍记载、历史典故</p>
                <a href="herb_list.php?type=culture" class="btn btn-outline-success">进入</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>