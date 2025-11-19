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
$loggedIn = isset($_SESSION['user']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$loggedIn) {
        $errors[] = '请先登录';
    }
    $name = $loggedIn ? $_SESSION['user']['username'] : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if ($content === '') {
        $errors[] = '请输入内容';
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
    <link href="styles.css" rel="stylesheet">
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
    <?php if(!$loggedIn): ?>
        <div class="alert alert-info mt-3">发帖需要登录 <a href="login.php" class="alert-link">去登录</a></div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-3">
            <div class="col-md-4">
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
            </div>
            <div class="col-md-8">
                <textarea name="content" class="form-control" rows="3" placeholder="分享心得或提问，500字以内"></textarea>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-success" type="submit">发布</button>
            </div>
        </form>
    <?php endif; ?>
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