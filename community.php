<?php
include 'config.php';
$storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$store = $storeDir . DIRECTORY_SEPARATOR . 'community_posts.json';
if (!is_dir($storeDir)) {
    mkdir($storeDir, 0777, true);
}
if (!file_exists($store)) {
    file_put_contents($store, json_encode([]));
}
$errors = [];
$posted = isset($_GET['posted']) ? 1 : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if ($name === '' || $content === '') {
        $errors[] = '请填写昵称和内容';
    }
    if (mb_strlen($name) > 50) {
        $errors[] = '昵称过长';
    }
    if (mb_strlen($content) > 500) {
        $errors[] = '内容过长';
    }
    if (empty($errors)) {
        $current = json_decode(file_get_contents($store), true);
        if (!is_array($current)) {
            $current = [];
        }
        $current[] = [
            'name' => $name,
            'content' => $content,
            'time' => date('Y-m-d H:i')
        ];
        file_put_contents($store, json_encode($current, JSON_UNESCAPED_UNICODE));
        header('Location: community.php?posted=1');
        exit;
    }
}
$list = json_decode(file_get_contents($store), true);
if (!is_array($list)) {
    $list = [];
}
$list = array_reverse($list);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>互动社区 - 本草平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'index.php'; ?>
<div class="container mt-5">
    <h2>互动社区</h2>
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach($errors as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if($posted): ?>
        <div class="alert alert-success mt-3">发布成功</div>
    <?php endif; ?>
    <form method="post" class="row g-3 mt-3">
        <div class="col-md-4">
            <input type="text" name="name" class="form-control" placeholder="昵称">
        </div>
        <div class="col-md-8">
            <textarea name="content" class="form-control" rows="3" placeholder="分享心得或提问，500字以内"></textarea>
        </div>
        <div class="col-12 text-end">
            <button class="btn btn-success" type="submit">发布</button>
        </div>
    </form>
    <div class="mt-4">
        <h4>最新帖子</h4>
        <?php if(empty($list)): ?>
            <div class="text-muted">还没有帖子，快来发布吧</div>
        <?php else: ?>
            <div class="list-group mt-2">
                <?php foreach($list as $p): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="text-muted"><?php echo htmlspecialchars($p['time'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="mt-2"><?php echo nl2br(htmlspecialchars($p['content'], ENT_QUOTES, 'UTF-8')); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>