<?php
include 'config.php';
$topic = isset($_GET['topic']) ? trim($_GET['topic']) : '';
$validTopics = ['药食同源','药用常识','园艺观赏','季节养生'];
if (!in_array($topic, $validTopics, true)) {
    $topic = '';
}
$map = ['药食同源' => '食疗', '药用常识' => '药用', '园艺观赏' => '观赏', '季节养生' => ''];
$cat = isset($map[$topic]) ? $map[$topic] : '';
$sqlBase = "FROM herbs WHERE 1=1";
$params = [];
if ($cat !== '') {
    $sqlBase .= " AND category = :c";
    $params[':c'] = $cat;
}
$stmt = $pdo->prepare("SELECT * " . $sqlBase . " ORDER BY create_time DESC LIMIT 6");
$stmt->execute($params);
$reco = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>科普专区 - 本草平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'index.php'; ?>
<div class="container mt-5">
    <h2>科普专区</h2>
    <form method="get" class="row g-3 mt-3">
        <div class="col-md-4">
            <select name="topic" class="form-select">
                <option value="">全部主题</option>
                <option value="药食同源" <?php echo $topic==='药食同源'?'selected':''; ?>>药食同源</option>
                <option value="药用常识" <?php echo $topic==='药用常识'?'selected':''; ?>>药用常识</option>
                <option value="园艺观赏" <?php echo $topic==='园艺观赏'?'selected':''; ?>>园艺观赏</option>
                <option value="季节养生" <?php echo $topic==='季节养生'?'selected':''; ?>>季节养生</option>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100" type="submit">筛选</button>
        </div>
    </form>
    <div class="mt-4">
        <?php if($topic==='药食同源'): ?>
            <div class="alert alert-success">精选食疗知识与本草推荐</div>
        <?php elseif($topic==='药用常识'): ?>
            <div class="alert alert-success">基础药用常识与安全提示</div>
        <?php elseif($topic==='园艺观赏'): ?>
            <div class="alert alert-success">园艺养护与观赏建议</div>
        <?php elseif($topic==='季节养生'): ?>
            <div class="alert alert-success">四时养生与节气食疗参考</div>
        <?php else: ?>
            <div class="alert alert-info">选择主题查看对应知识与推荐本草</div>
        <?php endif; ?>
    </div>
    <div class="mt-4">
        <h4>推荐本草</h4>
        <div class="row mt-3">
            <?php if(empty($reco)): ?>
                <div class="col-12 text-muted">暂无推荐</div>
            <?php else: ?>
                <?php foreach($reco as $herb): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php $img = isset($herb['image_url']) && $herb['image_url'] ? $herb['image_url'] : 'https://via.placeholder.com/400x200?text=Herb'; ?>
                            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>" style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($herb['alias'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="card-text"><?php echo htmlspecialchars(mb_substr($herb['effect'], 0, 60), ENT_QUOTES, 'UTF-8'); ?>...</p>
                                <a href="herb_detail.php?<?php echo htmlspecialchars(http_build_query(['id' => (int)$herb['id']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">查看详情</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>