<?php
include 'config.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if ($username === '' || $password === '') {
        $errors[] = '请输入用户名和密码';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = '用户名或密码错误';
        } else {
            $_SESSION['user'] = ['id' => (int)$user['id'], 'username' => $user['username'], 'role' => $user['role']];
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