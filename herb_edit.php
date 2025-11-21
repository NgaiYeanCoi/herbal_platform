<?php
include 'config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$updated = isset($_GET['updated']) ? 1 : 0;
$errors = [];
$isAdmin = isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin';
$stmt = $pdo->prepare('SELECT * FROM herbs WHERE id = :id');
$stmt->execute([':id' => $id]);
$herb = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$herb) {
    ob_start();
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger">未找到本草</div>
    </div>
    <?php
    $pageContent = ob_get_clean();
    include 'base.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    if (!$isAdmin) {
        $errors[] = '无权限';
    }
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $alias = isset($_POST['alias']) ? trim($_POST['alias']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';
    $effect = isset($_POST['effect']) ? trim($_POST['effect']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $food_recipe = isset($_POST['food_recipe']) ? trim($_POST['food_recipe']) : '';
    $property = isset($_POST['property']) ? trim($_POST['property']) : '';
    $attention = isset($_POST['attention']) ? trim($_POST['attention']) : '';
    $image_url_input = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $allowedCats = ['药用','食疗','观赏'];
    if ($name === '') {
        $errors[] = '请输入名称';
    }
    if ($category !== '' && !in_array($category, $allowedCats, true)) {
        $errors[] = '类别不合法';
    }
    $image_url_final = $image_url_input;
    if (isset($_FILES['image_file']) && isset($_FILES['image_file']['tmp_name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['image_file']['tmp_name'];
        $size = (int)$_FILES['image_file']['size'];
        $nameOrig = $_FILES['image_file']['name'];
        $ext = strtolower(pathinfo($nameOrig, PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowedExt, true)) {
            $errors[] = '图片格式不支持';
        }
        if ($size > 2 * 1024 * 1024) {
            $errors[] = '图片大小超过2MB';
        }
        $info = @getimagesize($tmp);
        if ($info === false) {
            $errors[] = '文件不是有效图片';
        }
        if (empty($errors)) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'herbs';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $filename = uniqid('herb_', true) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                $errors[] = '图片保存失败';
            } else {
                $image_url_final = 'uploads/herbs/' . $filename;
            }
        }
    }
    if ($image_url_final === '') {
        $image_url_final = $herb['image_url'];
    }
    if (empty($errors)) {
        $upd = $pdo->prepare('UPDATE herbs SET name = :name, alias = :alias, category = :category, origin = :origin, effect = :effect, description = :description, food_recipe = :food_recipe, property = :property, attention = :attention, image_url = :image_url WHERE id = :id');
        $upd->execute([
            ':name' => $name,
            ':alias' => $alias,
            ':category' => $category,
            ':origin' => $origin,
            ':effect' => $effect,
            ':description' => $description,
            ':food_recipe' => $food_recipe,
            ':property' => $property,
            ':attention' => $attention,
            ':image_url' => $image_url_final,
            ':id' => $id
        ]);
        header('Location: herb_edit.php?' . htmlspecialchars(http_build_query(['id' => $id, 'updated' => 1]), ENT_QUOTES, 'UTF-8'));
        exit;
    }
    $stmt = $pdo->prepare('SELECT * FROM herbs WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $herb = $stmt->fetch(PDO::FETCH_ASSOC);
}
ob_start();
?>
<div class="container mt-5" style="max-width:900px;">
    <h2>编辑本草</h2>
    <?php if(!$isAdmin): ?>
        <div class="alert alert-danger mt-3">无权限</div>
    <?php endif; ?>
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach($errors as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if($updated): ?>
        <div class="alert alert-success mt-3">更新成功</div>
    <?php endif; ?>
    <div class="row g-4 mt-2">
        <div class="col-md-4">
            <?php $img = isset($herb['image_url']) && $herb['image_url'] ? $herb['image_url'] : 'https://placehold.co/600x400?text=404'; ?>
            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid rounded border" alt="<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="col-md-8">
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <div class="mb-3">
                    <label class="form-label">名称</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">别名</label>
                    <input type="text" name="alias" class="form-control" value="<?php echo htmlspecialchars($herb['alias'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">类别</label>
                    <select name="category" class="form-select">
                        <option value="" <?php echo $herb['category']===''?'selected':''; ?>>请选择</option>
                        <option value="药用" <?php echo $herb['category']==='药用'?'selected':''; ?>>药用</option>
                        <option value="食疗" <?php echo $herb['category']==='食疗'?'selected':''; ?>>食疗</option>
                        <option value="观赏" <?php echo $herb['category']==='观赏'?'selected':''; ?>>观赏</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">产地</label>
                    <input type="text" name="origin" class="form-control" value="<?php echo htmlspecialchars($herb['origin'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">功效说明</label>
                    <textarea name="effect" class="form-control" rows="3"><?php echo htmlspecialchars($herb['effect'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">简介</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($herb['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">食疗配方（专业数据）</label>
                    <textarea name="food_recipe" class="form-control" rows="3"><?php echo htmlspecialchars($herb['food_recipe'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">性味归经（专业数据）</label>
                    <textarea name="property" class="form-control" rows="2"><?php echo htmlspecialchars($herb['property'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">注意事项（专业数据）</label>
                    <textarea name="attention" class="form-control" rows="2"><?php echo htmlspecialchars($herb['attention'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">图片URL（可选）</label>
                    <input type="text" name="image_url" class="form-control" value="<?php echo htmlspecialchars($herb['image_url'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">上传图片（可选）</label>
                    <input type="file" name="image_file" accept="image/*" class="form-control">
                </div>
                <div class="d-flex justify-content-between">
                    <a href="herb_list.php" class="btn btn-secondary">返回列表</a>
                    <button type="submit" class="btn btn-primary" <?php echo $isAdmin ? '' : 'disabled'; ?>>保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
$pageContent = ob_get_clean();
include 'base.php';
?>