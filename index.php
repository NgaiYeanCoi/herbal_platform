<?php
session_start();
include 'config.php';
ob_start();
?>
<div class="hero text-center" style="padding: 5rem 0; background: linear-gradient(rgba(46, 125, 50, 0.1), rgba(46, 125, 50, 0.05));">
    <div class="container">
        <h1 class="display-4 fw-bold text-success mb-4">探索中医药本草的智慧</h1>
        <p class="lead mt-3 text-dark fs-5 max-w-2xl mx-auto">
            汇聚千种本草植物的功效解析、食疗配方与专业数据，传承中医药文化精髓
        </p>
        <form action="herb_list.php" method="get" class="mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="input-group shadow-lg">
                        <input type="text" name="keyword" class="form-control fs-5 py-3" 
                               placeholder="输入植物名称、功效或配方..." required>
                        <button class="btn btn-success fs-5 py-3 px-5" type="submit">
                            <i class="bi bi-search me-1"></i> 搜索
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <div class="mt-4 text-muted">
            热门搜索: 
            <a href="herb_list.php?keyword=枸杞" class="text-success mx-1">枸杞</a>
            <a href="herb_list.php?keyword=黄芪" class="text-success mx-1">黄芪</a>
            <a href="herb_list.php?keyword=当归" class="text-success mx-1">当归</a>
            <a href="herb_list.php?keyword=金银花" class="text-success mx-1">金银花</a>
        </div>
    </div>
</div>

<!-- 特色分类区 -->
<div class="py-10 bg-white">
    <div class="container">
        <h2 class="text-center mb-8">本草分类导航</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-lg-3">
                <a href="herb_list.php?category=药用" class="text-decoration-none">
                    <div class="card h-100 text-center p-5 hover-shadow">
                        <div class="mb-3 fs-4 text-success">
                            <i class="bi bi-flask"></i>
                        </div>
                        <h3 class="card-title">药用植物</h3>
                        <p class="card-text text-muted mt-2">传统药用植物的功效与应用</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="herb_list.php?category=食疗" class="text-decoration-none">
                    <div class="card h-100 text-center p-5 hover-shadow">
                        <div class="mb-3 fs-4 text-success">
                            <i class="bi bi-utensils"></i>
                        </div>
                        <h3 class="card-title">食疗配方</h3>
                        <p class="card-text text-muted mt-2">药食同源的养生食疗方案</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-lg-3">
                <a href="herb_list.php?category=观赏" class="text-decoration-none">
                    <div class="card h-100 text-center p-5 hover-shadow">
                        <div class="mb-3 fs-4 text-success">
                            <i class="bi bi-palette"></i>
                        </div>
                        <h3 class="card-title">观赏植物</h3>
                        <p class="card-text text-muted mt-2">兼具观赏与药用价值的植物</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 热门本草推荐 -->
<div class="py-10 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-8">
            <h2>本草推荐</h2>
            <a href="herb_list.php" class="text-success">查看全部 <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php
            // 获取热门本草
            $stmt = $pdo->prepare("SELECT * FROM herbs ORDER BY RAND() LIMIT 4");
            $stmt->execute();
            $hotHerbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($hotHerbs as $herb):
                $img = isset($herb['image_url']) && $herb['image_url'] ? $herb['image_url'] : 'https://placehold.co/600x400?text=404';
            ?>
            <div class="col-md-3">
                <a href="herb_detail.php?id=<?php echo $herb['id']; ?>" class="text-decoration-none">
                    <div class="card h-100">
                        <img src="<?php echo htmlspecialchars($img); ?>" 
                             class="card-img-top herb-card-img" 
                             alt="<?php echo htmlspecialchars($herb['name']); ?>">
                        <div class="card-body">
                            <h5 class="card-title text-dark"><?php echo htmlspecialchars($herb['name']); ?></h5>
                            <p class="card-text text-muted small"><?php echo mb_substr($herb['effect'], 0, 60) . '...'; ?></p>
                            <span class="badge bg-success"><?php echo htmlspecialchars($herb['category']); ?></span>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>