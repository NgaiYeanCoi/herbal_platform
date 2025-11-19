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
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?> - 本草详情</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'index.php'; // 复用导航栏 ?>

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

            <div class="mt-4 alert alert-info">
                <h4>专业数据（仅供参考）</h4>
                <p><strong>性味归经：</strong><?php echo htmlspecialchars($herb['property'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>