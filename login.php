<?php
session_start();
include 'config.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : '';

    if (empty($username)) {
        $errors[] = '请输入用户名';
    }
    if (empty($password)) {
        $errors[] = '请输入密码';
    }
    if (empty($captcha)) {
        $errors[] = '请输入验证码';
    }
    if(empty($errors)){
           // 验证验证码
        if (!isset($_SESSION['captcha_code']) || strtolower($captcha) !== strtolower($_SESSION['captcha_code'])) {
            $errors[] = '验证码错误';
        }
    }  

    if(empty($errors)){
        $stmt = $pdo->prepare('SELECT id, username, password, user_type FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // 验证密码（使用表中 password 字段的哈希值）
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = '用户名或密码错误';
        } else {
            // 会话中存储 user_type
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'username' => $user['username'],
                'user_type' => $user['user_type'] // 修正为 user_type
            ];
            

            // 更新最后登录时间
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
         <div class="mb-3">
            <label class="form-label">验证码</label>
            <div class= "d-flex gap-2">
                <input type="text" name="captcha" class="form-control" required placeholder="请输入验证码">
                <img src="captcha.php" alt="验证码" style="width:150px; height:50px; cursor:pointer;" onclick="this.src='captcha.php?rand='+Math.random()">
            </div>
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