<?php
session_start();
include 'config.php';

// 权限验证：仅管理员可访问
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = '';

// 处理删除用户请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    // 禁止删除自己
    if ($userId === (int)$_SESSION['user']['id']) {
        $errors[] = '不能删除当前登录的管理员账号';
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $success = '用户已成功删除';
    }
}

// 处理编辑用户类型请求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $userType = isset($_POST['user_type']) ? $_POST['user_type'] : '';
    $allowedTypes = ['ordinary', 'professional', 'doctor', 'admin'];
    
    if (in_array($userType, $allowedTypes, true)) {
        // 禁止将自己降级
        if ($userId === (int)$_SESSION['user']['id'] && $userType !== 'admin') {
            $errors[] = '不能将自己的账号类型修改为非管理员';
        } else {
            $stmt = $pdo->prepare('UPDATE users SET user_type = :type, updated_at = NOW() WHERE id = :id');
            $stmt->execute([
                ':type' => $userType,
                ':id' => $userId
            ]);
            $success = '用户类型已更新';
        }
    } else {
        $errors[] = '无效的用户类型';
    }
}

// 获取所有用户列表
$stmt = $pdo->prepare('SELECT id, username, email, phone, user_type, created_at, last_login FROM users ORDER BY created_at DESC');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 用户类型中文映射
$userTypeMap = [
    'ordinary' => '普通用户',
    'professional' => '专业学习者',
    'doctor' => '基层医生',
    'admin' => '管理员'
];

ob_start();
?>

<!-- 页面主内容容器 -->
<div class="container mt-5">
    <h2>用户管理</h2>
    <p class="text-muted">管理平台所有注册用户信息</p>

    <!-- 消息提示 -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach ($errors as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success mt-3">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- 用户列表表格 -->
    <div class="card mt-4">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-success">
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>邮箱</th>
                        <th>手机号</th>
                        <th>用户类型</th>
                        <th>注册时间</th>
                        <th>最后登录</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo (int)$user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $user['phone'] ? htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8') : '-'; ?></td>
                        <td>
                            <span class="badge <?php 
                                switch($user['user_type']):
                                    case 'admin': echo 'bg-danger'; break;
                                    case 'doctor': echo 'bg-primary'; break;
                                    case 'professional': echo 'bg-info'; break;
                                    default: echo 'bg-secondary';
                                endswitch;
                            ?>">
                                <?php echo $userTypeMap[$user['user_type']]; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo $user['last_login'] ? htmlspecialchars($user['last_login'], ENT_QUOTES, 'UTF-8') : '未登录'; ?></td>
                        <td>
                            <!-- 编辑按钮 -->
                            <button type="button" class="btn btn-sm btn-warning me-1" 
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal<?php echo (int)$user['id']; ?>">
                                编辑
                            </button>
                            
                            <!-- 删除按钮 -->
                            <button type="button" class="btn btn-sm btn-danger" 
                                data-bs-toggle="modal" 
                                data-bs-target="#deleteModal<?php echo (int)$user['id']; ?>">
                                删除
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div> 
    </div> 
</div> 


<div class="modals-container" style="position: fixed; top: 0; left: 0; z-index: 1060;">
    <?php foreach ($users as $user): ?>
        <!-- 编辑用户模态框 -->
        <div class="modal fade" id="editModal<?php echo (int)$user['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog"> 
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑用户：<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">用户类型</label>
                                <select name="user_type" class="form-select" required>
                                    <?php foreach ($userTypeMap as $key => $label): ?>
                                    <option value="<?php echo $key; ?>" 
                                        <?php echo $user['user_type'] === $key ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-primary">保存修改</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- 删除确认模态框 -->
        <div class="modal fade" id="deleteModal<?php echo (int)$user['id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">确认删除</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        确定要删除用户 <strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></strong> 吗？
                        <p class="text-danger mt-2 fs-6">此操作不可恢复，请谨慎操作！</p>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                            <button type="submit" class="btn btn-danger">确认删除</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>