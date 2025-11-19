<?php
include 'config.php';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'time_desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9;
$typeToCategory = [
    'professional' => '药用',
    'doctor' => '药用',
    'public' => '食疗',
    'culture' => '观赏'
];
if ($category === '' && $type !== '' && isset($typeToCategory[$type])) {
    $category = $typeToCategory[$type];
}
$sqlBase = "FROM herbs WHERE 1=1";
$params = [];
if ($keyword !== '') {
    $sqlBase .= " AND (name LIKE :keyword OR alias LIKE :keyword OR effect LIKE :keyword)";
    $params[':keyword'] = "%$keyword%";
}
if ($category !== '') {
    $sqlBase .= " AND category = :category";
    $params[':category'] = $category;
}
$sortWhitelist = [
    'name_asc' => 'name ASC',
    'name_desc' => 'name DESC',
    'time_desc' => 'create_time DESC',
    'time_asc' => 'create_time ASC'
];
$orderBy = isset($sortWhitelist[$sort]) ? $sortWhitelist[$sort] : $sortWhitelist['time_desc'];
$countStmt = $pdo->prepare("SELECT COUNT(*) " . $sqlBase);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}
$offset = ($page - 1) * $perPage;
$listSql = "SELECT * " . $sqlBase . " ORDER BY " . $orderBy . " LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
$stmt = $pdo->prepare($listSql);
$stmt->execute($params);
$herbs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$qsBase = [
    'keyword' => $keyword,
    'category' => $category,
    'sort' => $sort,
    'type' => $type
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>本草库 - 本草植物平台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'index.php'; // 复用导航栏 ?>

<div class="container mt-5">
    <h2>本草列表</h2>
    <div class="mt-3">
        <form method="get" class="row g-3">
            <?php if($type !== ''): ?>
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($type, ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <div class="col-md-4">
                <input type="text" name="keyword" class="form-control" placeholder="输入植物名称、功效..." value="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
                <select name="category" class="form-select">
                    <option value="">全部类别</option>
                    <option value="药用" <?php echo $category==='药用'?'selected':''; ?>>药用</option>
                    <option value="食疗" <?php echo $category==='食疗'?'selected':''; ?>>食疗</option>
                    <option value="观赏" <?php echo $category==='观赏'?'selected':''; ?>>观赏</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="sort" class="form-select">
                    <option value="time_desc" <?php echo $sort==='time_desc'?'selected':''; ?>>最新发布</option>
                    <option value="time_asc" <?php echo $sort==='time_asc'?'selected':''; ?>>最早发布</option>
                    <option value="name_asc" <?php echo $sort==='name_asc'?'selected':''; ?>>名称升序</option>
                    <option value="name_desc" <?php echo $sort==='name_desc'?'selected':''; ?>>名称降序</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success w-100" type="submit">筛选</button>
            </div>
        </form>
        <div class="mt-2 text-muted">
            <?php if($keyword !== ''): ?>
                <span>搜索：<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <?php if($category !== ''): ?>
                <span class="ms-3">类别：<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <span class="ms-3">共 <?php echo (int)$total; ?> 条</span>
        </div>
    </div>
    <div class="row mt-4">
        <?php if(empty($herbs)): ?>
            <div class="col-12 text-center py-5">
                <p>未找到相关本草，请尝试其他关键词</p>
            </div>
        <?php else: ?>
            <?php foreach($herbs as $herb): ?>
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
    <?php if($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php $prevPage = max(1, $page-1); $nextPage = min($totalPages, $page+1); ?>
                <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                    <a class="page-link" href="herb_list.php?<?php echo htmlspecialchars(http_build_query(array_merge($qsBase, ['page' => $prevPage])), ENT_QUOTES, 'UTF-8'); ?>">上一页</a>
                </li>
                <?php for($i=1;$i<=$totalPages;$i++): ?>
                    <li class="page-item <?php echo $i===$page?'active':''; ?>">
                        <a class="page-link" href="herb_list.php?<?php echo htmlspecialchars(http_build_query(array_merge($qsBase, ['page' => $i])), ENT_QUOTES, 'UTF-8'); ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                    <a class="page-link" href="herb_list.php?<?php echo htmlspecialchars(http_build_query(array_merge($qsBase, ['page' => $nextPage])), ENT_QUOTES, 'UTF-8'); ?>">下一页</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>
</body>
</html>