<?php
session_start();
include 'config.php';
$storeDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
$store = $storeDir . DIRECTORY_SEPARATOR . 'community_posts.json';

// 初始化存储目录和文件
if (!is_dir($storeDir)) {
    mkdir($storeDir, 0777, true);
}
if (!file_exists($store)) {
    file_put_contents($store, json_encode([]));
}

$errors = [];
$posted = isset($_GET['posted']) ? 1 : 0;
$deleted = isset($_GET['deleted']) ? 1 : 0; // 新增：删除成功标识
$loggedIn = isset($_SESSION['user']);

// 处理发布请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    if (!$loggedIn) {
        $errors[] = '请先登录';
    }
    $name = $loggedIn ? $_SESSION['user']['username'] : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    
    if ($content === '') {
        $errors[] = '请输入内容';
    }
    if (mb_strlen($content) > 500) {
        $errors[] = '内容过长（限500字）';
    }
    
    if (empty($errors)) {
        $current = json_decode(file_get_contents($store), true) ?: [];
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

// 处理删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_index'])) {
    if (!$loggedIn) {
        $errors[] = '请先登录';
    } else {
        $index = (int)$_POST['delete_index'];
        $current = json_decode(file_get_contents($store), true) ?: [];
        
        // 验证索引有效性
        if (isset($current[$index])) {
            $post = $current[$index];
            // 权限校验：本人或管理员可删除
            $isOwner = ($post['name'] === $_SESSION['user']['username']);
            $isAdmin = ($_SESSION['user']['user_type'] === 'admin');
            
            if ($isOwner || $isAdmin) {
                array_splice($current, $index, 1); // 移除对应帖子
                file_put_contents($store, json_encode($current, JSON_UNESCAPED_UNICODE));
                header('Location: community.php?deleted=1');
                exit;
            } else {
                $errors[] = '无权限删除此帖子';
            }
        } else {
            $errors[] = '帖子不存在';
        }
    }
}

// 读取帖子列表（反转后按时间倒序显示）
$originalList = json_decode(file_get_contents($store), true) ?: [];
$list = array_reverse($originalList); // 最新的在前面

ob_start();
?>

<div class="container mt-5">
    <h2>互动社区</h2>
    
    <!-- 错误提示 -->
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach($errors as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- 操作成功提示 -->
    <?php if($posted): ?>
        <div class="alert alert-success mt-3">发布成功！</div>
    <?php endif; ?>
    <?php if($deleted): ?>
        <div class="alert alert-success mt-3">删除成功！</div>
    <?php endif; ?>
    
    <!-- 发布表单 -->
    <?php if(!$loggedIn): ?>
        <div class="alert alert-info mt-3">发帖需要登录 <a href="login.php" class="alert-link">去登录</a></div>
    <?php else: ?>
        <form method="post" class="row g-3 mt-3">
            <div class="col-md-4">
                <input type="text" class="form-control" 
                       value="<?php echo htmlspecialchars($_SESSION['user']['username'], ENT_QUOTES, 'UTF-8'); ?>" 
                       disabled>
            </div>
            <div class="col-md-8">
                <textarea name="content" class="form-control" rows="3" 
                          placeholder="分享心得或提问，500字以内" required></textarea>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-success" type="submit">发布</button>
                <button class="btn btn-secondary" type="reset">重置</button>
            </div>
        </form>
    <?php endif; ?>
    
    <!-- 帖子列表 -->
    <div class="mt-4">
        <h4>最新帖子</h4>
        <?php if(empty($list)): ?>
            <div class="text-muted">还没有帖子，快来发布第一条吧~</div>
        <?php else: ?>
            <div class="list-group mt-2">
                <?php foreach($list as $key => $p): 
                    $originalIndex = count($originalList) - 1 - $key;
                ?>
                    <div class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <strong class='text-primary'>
                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </strong>
                            <div class = "d-flex flex-column align-items-end">
                                <span class="text-muted small">
                                    <?php echo htmlspecialchars($p['time'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <!-- 删除按钮 -->
                            <?php if($loggedIn && (
                                $p['name'] === $_SESSION['user']['username'] || 
                                $_SESSION['user']['user_type'] === 'admin'
                            )): ?>
                                <form method="post" onsubmit="return confirm('确定要删除这条帖子吗？');" class="mt-1 ">
                                    <input type="hidden" name="delete_index" value="<?php echo $originalIndex; ?>">
                                    <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </span>
                        </div>
                        <div class="mt-3"><?php echo nl2br(htmlspecialchars($p['content'], ENT_QUOTES, 'UTF-8')); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>