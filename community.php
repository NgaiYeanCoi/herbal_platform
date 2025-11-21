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
$deleted = isset($_GET['deleted']) ? 1 : 0; // 帖子删除成功标识
$replied = isset($_GET['replied']) ? 1 : 0; // 回复成功标识
$replyDeleted = isset($_GET['reply_deleted']) ? 1 : 0; // 新增：回复删除成功标识
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
        // 确保新帖子有 replies 数组（兼容旧数据）
        $current[] = [
            'name' => $name,
            'content' => $content,
            'time' => date('Y-m-d H:i'),
            'replies' => []
        ];
        file_put_contents($store, json_encode($current, JSON_UNESCAPED_UNICODE));
        header('Location: community.php?posted=1');
        exit;
    }
}

// 处理帖子删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_index']) && !isset($_POST['reply_index'])) {
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

// 处理回复提交请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content']) && isset($_POST['post_index'])) {
    if (!$loggedIn) {
        $errors[] = '请先登录才能回复';
    } else {
        $postIndex = (int)$_POST['post_index'];
        $replyContent = trim($_POST['reply_content']);
        
        if ($replyContent === '') {
            $errors[] = '回复内容不能为空';
        }
        if (mb_strlen($replyContent) > 300) {
            $errors[] = '回复内容过长（限300字）';
        }
        
        if (empty($errors)) {
            $current = json_decode(file_get_contents($store), true) ?: [];
            
            if (isset($current[$postIndex])) {
                // 确保帖子有 replies 数组（兼容旧数据）
                if (!isset($current[$postIndex]['replies']) || !is_array($current[$postIndex]['replies'])) {
                    $current[$postIndex]['replies'] = [];
                }
                // 添加新回复
                $current[$postIndex]['replies'][] = [
                    'name' => $_SESSION['user']['username'],
                    'content' => $replyContent,
                    'time' => date('Y-m-d H:i')
                ];
                
                file_put_contents($store, json_encode($current, JSON_UNESCAPED_UNICODE));
                header('Location: community.php?replied=1');
                exit;
            } else {
                $errors[] = '帖子不存在，无法回复';
            }
        }
    }
}

// 新增：处理回复删除请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_index']) && isset($_POST['reply_index'])) {
    if (!$loggedIn) {
        $errors[] = '请先登录才能删除回复';
    } else {
        $postIndex = (int)$_POST['post_index'];
        $replyIndex = (int)$_POST['reply_index'];
        $current = json_decode(file_get_contents($store), true) ?: [];
        
        // 验证帖子和回复是否存在
        if (!isset($current[$postIndex])) {
            $errors[] = '帖子不存在，无法删除回复';
        } elseif (!isset($current[$postIndex]['replies']) || !is_array($current[$postIndex]['replies']) || !isset($current[$postIndex]['replies'][$replyIndex])) {
            $errors[] = '回复不存在';
        } else {
            $reply = $current[$postIndex]['replies'][$replyIndex];
            // 权限校验：回复作者或管理员可删除
            $isReplyOwner = ($reply['name'] === $_SESSION['user']['username']);
            $isAdmin = ($_SESSION['user']['user_type'] === 'admin');
            
            if ($isReplyOwner || $isAdmin) {
                // 移除指定回复
                array_splice($current[$postIndex]['replies'], $replyIndex, 1);
                file_put_contents($store, json_encode($current, JSON_UNESCAPED_UNICODE));
                header('Location: community.php?reply_deleted=1');
                exit;
            } else {
                $errors[] = '无权限删除此回复';
            }
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
    <?php if($replied): ?>
        <div class="alert alert-success mt-3">回复成功！</div>
    <?php endif; ?>
    <?php if($replyDeleted): ?>
        <div class="alert alert-success mt-3">回复删除成功！</div>
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
                    $replies = isset($p['replies']) && is_array($p['replies']) ? $p['replies'] : [];
                ?>
                    <div class="list-group-item p-4 mb-3 shadow-sm border rounded">
                        <!-- 帖子头部：作者、时间、删除按钮 -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <strong class="text-primary fs-6">
                                <?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>
                            </strong>
                            <div class="d-flex flex-column align-items-end">
                                <span class="text-muted small">
                                    <?php echo htmlspecialchars($p['time'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <!-- 帖子删除按钮 -->
                                <?php if($loggedIn && (
                                    $p['name'] === $_SESSION['user']['username'] || 
                                    $_SESSION['user']['user_type'] === 'admin'
                                )): ?>
                                    <form method="post" onsubmit="return confirm('确定要删除这条帖子吗？');" class="mt-1">
                                        <input type="hidden" name="delete_index" value="<?php echo $originalIndex; ?>">
                                        <button class="btn btn-outline-danger btn-sm" type="submit">删除帖子</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- 帖子内容 -->
                        <div class="post-content mb-4">
                            <?php echo nl2br(htmlspecialchars($p['content'], ENT_QUOTES, 'UTF-8')); ?>
                        </div>
                        
                        <!-- 回复表单 -->
                        <div class="reply-form mb-4">
                            <?php if(!$loggedIn): ?>
                                <small class="text-muted">
                                    <a href="login.php" class="text-primary">登录</a> 后可回复
                                </small>
                            <?php else: ?>
                                <form method="post" class="row g-2">
                                    <input type="hidden" name="post_index" value="<?php echo $originalIndex; ?>">
                                    <div class="col-10">
                                        <textarea name="reply_content" class="form-control" rows="2" 
                                                  placeholder="回复 @<?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?>，300字以内" required></textarea>
                                    </div>
                                    <div class="col-2 d-flex align-items-end">
                                        <button class="btn btn-primary btn-sm w-100" type="submit">回复</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 回复列表 -->
                        <?php if(!empty($replies)): ?>
                            <div class="replies-list mt-4 pt-3 border-top">
                                <h6 class="text-muted mb-3">回复 (<?php echo count($replies); ?>)</h6>
                                <?php foreach($replies as $replyKey => $r): ?>
                                    <div class="list-group-item p-3 mb-2 bg-light rounded-2 border">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <span class="font-weight-bold text-success">
                                                    <?php echo htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                                <span class="text-muted small ms-2">
                                                    <?php echo htmlspecialchars($r['time'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </div>
                                            <!-- 回复删除按钮 -->
                                            <?php if($loggedIn && (
                                                $r['name'] === $_SESSION['user']['username'] || 
                                                $_SESSION['user']['user_type'] === 'admin'
                                            )): ?>
                                                <form method="post" onsubmit="return confirm('确定要删除这条回复吗？');" class="mb-0">
                                                    <input type="hidden" name="post_index" value="<?php echo $originalIndex; ?>">
                                                    <input type="hidden" name="reply_index" value="<?php echo $replyKey; ?>">
                                                    <button class="btn btn-outline-danger btn-sm" type="submit">删除</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reply-content">
                                            <?php echo nl2br(htmlspecialchars($r['content'], ENT_QUOTES, 'UTF-8')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
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