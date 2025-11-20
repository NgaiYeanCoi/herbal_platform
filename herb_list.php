<?php
session_start();
include 'config.php';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'time_desc';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;
$deleted = isset($_GET['deleted']) ? 1 : 0;
$created = isset($_GET['created']) ? 1 : 0;
$createErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'admin') {
        $createErrors[] = '无权限';
    }
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $alias = isset($_POST['alias']) ? trim($_POST['alias']) : '';
    $postCategory = isset($_POST['category']) ? trim($_POST['category']) : '';
    $origin = isset($_POST['origin']) ? trim($_POST['origin']) : '';
    $effect = isset($_POST['effect']) ? trim($_POST['effect']) : '';
    $food_recipe = isset($_POST['food_recipe']) ? trim($_POST['food_recipe']) : '';
    $property = isset($_POST['property']) ? trim($_POST['property']) : '';
    $image_url = isset($_POST['image_url']) ? trim($_POST['image_url']) : '';
    $allowedCats = ['药用','食疗','观赏'];
    if ($name === '') {
        $createErrors[] = '请输入名称';
    }
    if ($postCategory !== '' && !in_array($postCategory, $allowedCats, true)) {
        $createErrors[] = '类别不合法';
    }
    if (isset($_FILES['image_file']) && isset($_FILES['image_file']['tmp_name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['image_file']['tmp_name'];
        $size = (int)$_FILES['image_file']['size'];
        $nameOrig = $_FILES['image_file']['name'];
        $ext = strtolower(pathinfo($nameOrig, PATHINFO_EXTENSION));
        $allowedExt = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowedExt, true)) {
            $createErrors[] = '图片格式不支持';
        }
        if ($size > 2 * 1024 * 1024) {
            $createErrors[] = '图片大小超过2MB';
        }
        $info = @getimagesize($tmp);
        if ($info === false) {
            $createErrors[] = '文件不是有效图片';
        }
        if (empty($createErrors)) {
            $dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'herbs';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $filename = uniqid('herb_', true) . '.' . $ext;
            $dest = $dir . DIRECTORY_SEPARATOR . $filename;
            if (!move_uploaded_file($tmp, $dest)) {
                $createErrors[] = '图片保存失败';
            } else {
                $image_url = 'uploads/herbs/' . $filename;
            }
        }
    }
    if (empty($createErrors)) {
        $ins = $pdo->prepare('INSERT INTO herbs (name, alias, category, origin, effect, food_recipe, property, image_url, create_time) VALUES (:name, :alias, :category, :origin, :effect, :food_recipe, :property, :image_url, NOW())');
        $ins->execute([
            ':name' => $name,
            ':alias' => $alias,
            ':category' => $postCategory,
            ':origin' => $origin,
            ':effect' => $effect,
            ':food_recipe' => $food_recipe,
            ':property' => $property,
            ':image_url' => $image_url
        ]);
        header('Location: herb_list.php?created=1');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteErrors = [];
    if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'admin') {
        $deleteErrors[] = '无权限';
    }
    $delId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($delId <= 0) {
        $deleteErrors[] = '参数错误';
    }
    if (empty($deleteErrors)) {
        $imgStmt = $pdo->prepare('SELECT image_url FROM herbs WHERE id = :id');
        $imgStmt->execute([':id' => $delId]);
        $row = $imgStmt->fetch(PDO::FETCH_ASSOC);
        if ($row && isset($row['image_url']) && $row['image_url'] !== '' && strpos($row['image_url'], 'uploads/herbs/') === 0) {
            $filePath = __DIR__ . DIRECTORY_SEPARATOR . $row['image_url'];
            if (is_file($filePath)) {
                @unlink($filePath);
            }
        }
        $delStmt = $pdo->prepare('DELETE FROM herbs WHERE id = :id');
        $delStmt->execute([':id' => $delId]);
        header('Location: herb_list.php?deleted=1');
        exit;
    }
}
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
ob_start();
?>
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
            <div class="col-md-2 d-flex">
                <?php if(isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin'): ?>
                    <button class="btn btn-success me-2" type="submit">筛选</button>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addHerbModal">新增</button>
                    <?php else: ?>
                <button class="btn btn-success flex-fill me-2" type="submit">筛选</button>
                <?php endif; ?>
            </div>
        </form>
        <?php if(!empty($createErrors)): ?>
            <div class="alert alert-danger mt-2">
                <?php foreach($createErrors as $e): ?>
                    <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if($created): ?>
            <div class="alert alert-success mt-2">新增成功</div>
        <?php endif; ?>
        <?php if(isset($deleteErrors) && !empty($deleteErrors)): ?>
            <div class="alert alert-danger mt-2">
                <?php foreach($deleteErrors as $e): ?>
                    <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if($deleted): ?>
            <div class="alert alert-success mt-2">删除成功</div>
        <?php endif; ?>
        <?php if(isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin'): ?>
        <div class="modal fade" id="addHerbModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">新增本草</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create">
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="text" name="name" class="form-control" placeholder="名称" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="alias" class="form-control" placeholder="别名">
                                </div>
                                <div class="col-md-6">
                                    <select name="category" class="form-select">
                                        <option value="">类别</option>
                                        <option value="药用">药用</option>
                                        <option value="食疗">食疗</option>
                                        <option value="观赏">观赏</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" name="origin" class="form-control" placeholder="产地">
                                </div>
                                <div class="col-12">
                                    <textarea name="effect" class="form-control" rows="3" placeholder="功效说明"></textarea>
                                </div>
                                <div class="col-12">
                                    <textarea name="food_recipe" class="form-control" rows="3" placeholder="食疗配方"></textarea>
                                </div>
                                <div class="col-12">
                                    <textarea name="property" class="form-control" rows="2" placeholder="性味归经"></textarea>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="image_url" class="form-control" placeholder="图片URL（可选）">
                                </div>
                                <div class="col-12">
                                    <input type="file" name="image_file" accept="image/*" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">保存</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
                        <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" class="card-img-top herb-card-img" alt="<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($herb['alias'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="card-text"><?php echo htmlspecialchars(mb_substr($herb['effect'], 0, 60), ENT_QUOTES, 'UTF-8'); ?>...</p>
                            <a href="herb_detail.php?<?php echo htmlspecialchars(http_build_query(['id' => (int)$herb['id']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">查看详情</a>
                            <?php if(isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin'): ?>
                            <a href="herb_edit.php?<?php echo htmlspecialchars(http_build_query(['id' => (int)$herb['id']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-warning">编辑</a>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo (int)$herb['id']; ?>">删除</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if(isset($_SESSION['user']) && $_SESSION['user']['user_type'] === 'admin'): ?>
                <div class="modal fade" id="deleteModal<?php echo (int)$herb['id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">确认删除</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$herb['id']; ?>">
                                <div class="modal-body">
                                    确定删除 “<?php echo htmlspecialchars($herb['name'], ENT_QUOTES, 'UTF-8'); ?>”？
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                                    <button type="submit" class="btn btn-danger">删除</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
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
<?php
$pageContent = ob_get_clean();
include 'base.php';