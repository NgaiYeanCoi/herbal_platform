<?php
// 开启会话（记录登录状态）
session_start();
include 'config.php';

// 创建符合结构的users表（仅在表不存在时执行）
$pdo->exec('CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT \'用户ID\',
  `username` varchar(50) NOT NULL COMMENT \'用户名（唯一）\',
  `password` varchar(255) NOT NULL COMMENT \'密码（加密存储）\',
  `email` varchar(100) NOT NULL COMMENT \'邮箱（唯一）\',
  `phone` varchar(20) DEFAULT NULL COMMENT \'手机号\',
  `user_type` enum(\'ordinary\',\'professional\',\'doctor\',\'admin\') DEFAULT \'ordinary\' COMMENT \'用户类型\',
  `avatar` varchar(500) DEFAULT \'\' COMMENT \'头像URL\',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP COMMENT \'注册时间\',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT \'更新时间\',
  `last_login` datetime DEFAULT NULL COMMENT \'最后登录时间\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_user_type` (`user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=\'用户信息表\'');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据并过滤
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null; // 可选字段
    $user_type = isset($_POST['user_type']) ? $_POST['user_type'] : 'ordinary';

    // 1. 验证用户类型（匹配枚举值）
    $userTypeWhitelist = ['ordinary', 'professional', 'doctor']; // 排除admin，普通注册不允许
    if (!in_array($user_type, $userTypeWhitelist, true)) {
        $user_type = 'ordinary';
    }

    // 2. 表单验证
    if (empty($username) || empty($password) || empty($email)) {
        $errors[] = '用户名、密码和邮箱为必填项';
    }
    // 用户名格式：3-20位，仅含字母、数字、下划线
    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = '用户名需3-20位，仅含字母、数字或下划线';
    }
    // 密码长度
    if (strlen($password) < 6) {
        $errors[] = '密码长度不少于6位';
    }
    // 邮箱格式验证
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    }
    // 手机号可选，若填写则验证格式（11位数字）
    if ($phone !== null && !empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
        $errors[] = '手机号格式不正确（11位数字）';
    }

    // 3. 验证唯一性（用户名和邮箱）
    if (empty($errors)) {
        // 检查用户名是否已存在
        $checkUsername = $pdo->prepare('SELECT id FROM users WHERE username = :u');
        $checkUsername->execute([':u' => $username]);
        if ($checkUsername->fetch()) {
            $errors[] = '用户名已被注册';
        }

        // 检查邮箱是否已存在
        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :e');
        $checkEmail->execute([':e' => $email]);
        if ($checkEmail->fetch()) {
            $errors[] = '邮箱已被注册';
        }
    }

    // 4. 插入用户数据
    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        // 插入语句包含表中必填字段（username/password/email/user_type）
        $insert = $pdo->prepare("INSERT INTO users 
            (username, password, email, phone, user_type) 
            VALUES (:u, :p, :e, :ph, :t)");
        
        $insert->execute([
            ':u' => $username,
            ':p' => $passwordHash,
            ':e' => $email,
            ':ph' => $phone, // 允许为null
            ':t' => $user_type
        ]);

        // 记录会话
        $userId = (int)$pdo->lastInsertId();
        $_SESSION['user'] = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'user_type' => $user_type
        ];

        $registerSuccess = true;
    }
}
?>

<div class="container mt-5" style="max-width:600px;">
    <h2>用户注册</h2>
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="mt-3">
        <div class="mb-3">
            <label class="form-label">用户名 <span class="text-danger">*</span></label>
            <input type="text" name="username" class="form-control" required
                   placeholder="3-20位，含字母、数字或下划线">
        </div>

        <div class="mb-3">
            <label class="form-label">密码 <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required
                   placeholder="不少于6位">
        </div>

        <div class="mb-3">
            <label class="form-label">邮箱 <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required
                   placeholder="用于登录和找回密码">
        </div>

        <div class="mb-3">
            <label class="form-label">手机号（可选）</label>
            <input type="tel" name="phone" class="form-control"
                   placeholder="11位数字，如：13800138000">
        </div>

        <div class="mb-3">
            <label class="form-label">用户类型 <span class="text-danger">*</span></label>
            <select name="user_type" class="form-select" required>
                <option value="ordinary">普通用户</option>
                <option value="professional">专业学习者</option>
                <option value="doctor">基层医生</option>
            </select>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <button class="btn btn-success" type="submit">注册</button>
            <a href="login.php">已有账户？去登录</a>
        </div>
    </form>
</div>
<!-- 注册成功模态框 -->
<div class="modal fade" id="registerSuccessModal" tabindex="-1" aria-labelledby="registerSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registerSuccessModalLabel">注册成功</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                恭喜您注册成功！即将为您跳转到首页~
            </div>
            <div class="modal-footer">
                <!-- 确认按钮：点击后跳转到首页 -->
                <button type="button" class="btn btn-success" onclick="window.location.href='index.php'">确认</button>
            </div>
        </div>
    </div>
</div>
<!-- 注册成功后自动显示模态框 -->
<?php if (isset($registerSuccess) && $registerSuccess): ?>
<script>
    // 页面加载完成后显示模态框
    document.addEventListener('DOMContentLoaded', function() {
        var successModal = new bootstrap.Modal(document.getElementById('registerSuccessModal'));
        successModal.show();

        // 3秒后自动跳转
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 3000);
    });
</script>
<?php endif; ?>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>