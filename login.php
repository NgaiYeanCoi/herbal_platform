<?php
// 1. 开启会话（必须添加，否则无法存储登录状态）
session_start();
include 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === '' || $password === '') {
        $errors[] = '请输入用户名和密码';
    } else {
        // 2. 修正查询字段：将 password_hash 改为 password（匹配表结构）
        //    角色字段 role 改为 user_type（与表中字段一致）
        $stmt = $pdo->prepare('SELECT id, username, password, user_type FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 3. 验证密码（使用表中 password 字段的哈希值）
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = '用户名或密码错误';
        } else {
            // 4. 会话中存储 user_type（而非 role，保持字段一致）
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'user_type' => $user['user_type'] // 修正为 user_type
            ];

            // 更新最后登录时间（可选增强功能）
            $updateLogin = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
            $updateLogin->execute([':id' => $user['id']]);

            header('Location: index.php');
            exit;
        }
    }
}

ob_start();
?>

<div class="container mt-5" style="max-width:600px;">
    <h2>登录</h2>
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach($errors as $e): ?>
                <div><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label class="form-label">用户名</label>
            <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">密码</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-success" type="submit">登录</button>
            <a href="register.php">没有账户？注册</a>
        </div>
    </form>
</div>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>