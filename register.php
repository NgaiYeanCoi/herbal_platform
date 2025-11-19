<?php
include 'config.php';
$pdo->exec('CREATE TABLE IF NOT EXISTS users (
  id int(11) PRIMARY KEY AUTO_INCREMENT,
  username varchar(50) NOT NULL UNIQUE,
  password_hash varchar(255) NOT NULL,
  role varchar(20) NOT NULL DEFAULT "user",
  created_at timestamp DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8');
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'user';
    $roleWhitelist = ['user','professional','doctor'];
    if (!in_array($role, $roleWhitelist, true)) {
        $role = 'user';
    }
    if ($username === '' || $password === '') {
        $errors[] = '请输入用户名和密码';
    }
    if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        $errors[] = '用户名长度需在3-20之间';
    }
    if (strlen($password) < 6) {
        $errors[] = '密码长度不少于6位';
    }
    if (empty($errors)) {
        $check = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            $errors[] = '用户名已存在';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (:u, :p, :r)');
            $ins->execute([':u' => $username, ':p' => $hash, ':r' => $role]);
            $id = (int)$pdo->lastInsertId();
            $_SESSION['user'] = ['id' => $id, 'username' => $username, 'role' => $role];
            header('Location: index.php');
            exit;
        }
    }
}
?>

<div class="container mt-5" style="max-width:600px;">
    <h2>注册</h2>
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
            <label class="form-label">角色</label>
            <select name="role" class="form-select">
                <option value="user">普通用户</option>
                <option value="professional">专业学习者</option>
                <option value="doctor">基层医生</option>
            </select>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-success" type="submit">注册</button>
            <a href="login.php">已有账户？登录</a>
        </div>
    </form>
</div>
<?php
$pageContent = ob_get_clean();
include 'base.php';