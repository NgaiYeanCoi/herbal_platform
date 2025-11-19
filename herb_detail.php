<?php
include 'config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 获取植物详情
$stmt = $pdo->prepare("SELECT * FROM herbs WHERE id = :id");
$stmt->execute([':id' => $id]);
$herb = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$herb) {
    die("未找到该本草信息");
}
ob_start();
?>
<div class="container mt-5">
    <div class="row">
        <div class="col-md-4">
            <?php $img = isset($herb['image_url']) && $herb['image_url'] ? $herb['image_url'] : 'https://via.placeholder.com/600x400?text=Herb'; ?>
            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-8">
            <h2><?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="text-muted"><strong>别名：</strong><?php echo htmlspecialchars($herb['alias'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>类别：</strong><?php echo htmlspecialchars($herb['category'], ENT_QUOTES, 'UTF-8'); ?></p>
            <p><strong>产地：</strong><?php echo htmlspecialchars($herb['origin'], ENT_QUOTES, 'UTF-8'); ?></p>

            <div class="mt-4">
                <h4>功效说明</h4>
                <p><?php echo htmlspecialchars($herb['effect'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div class="mt-4">
                <h4>食疗配方</h4>
                <p><?php echo htmlspecialchars($herb['food_recipe'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <?php $isPro = isset($_SESSION['user']) && in_array($_SESSION['user']['user_type'], ['professional','doctor','admin']); ?>
            <div class="mt-4">
                <div class="card pro-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>专业数据</span>
                        <?php if($isPro): ?>
                            <span class="badge bg-success">专业视图</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">基础视图</span>
                        <?php endif; ?>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if($isPro): ?>
                        <li class="list-group-item"><strong>性味归经：</strong><?php echo htmlspecialchars($herb['property'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li class="list-group-item"><strong>食疗配方：</strong><?php echo htmlspecialchars($herb['food_recipe'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <!-- TODO:代补充功能 -->
                        <!-- <li class="list-group-item"><strong>主治疾病：</strong><?php echo htmlspecialchars($herb['diseases'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <li class="list-group-item"><strong>注意事项：</strong><?php echo htmlspecialchars($herb['attention'], ENT_QUOTES, 'UTF-8'); ?></li> -->
                            <li class="list-group-item text-muted">所有信息仅供学习参考，具体用法须遵医嘱</li>
                        <?php else: ?>
                            <li class="list-group-item text-muted">登录为专业/医生可查看更多提示</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include 'base.php';