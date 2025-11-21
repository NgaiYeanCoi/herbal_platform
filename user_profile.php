<?php
session_start();
include 'config.php';

// 检查用户是否登录，未登录则跳转至登录页
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$errors = [];
$success = '';

// 获取用户当前信息
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("用户不存在");
}

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $captcha = isset($_POST['captcha']) ? $_POST['captcha'] : '';

    // 验证邮箱
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = '请输入有效的邮箱地址';
    }

    // 验证手机号（可选）
    if ($phone !== null && !empty($phone) && !preg_match('/^1[3-9]\d{9}$/', $phone)) {
        $errors[] = '手机号格式不正确（11位数字）';
    }

    // 检查邮箱是否被占用（排除当前用户）
    if (empty($errors)) {
        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :e AND id != :id');
        $checkEmail->execute([':e' => $email, ':id' => $userId]);
        if ($checkEmail->fetch()) {
            $errors[] = '邮箱已被注册';
        }
    }

    // 处理密码修改（如果填写了新密码）
    if (!empty($newPassword)) {
        // 验证当前密码
        if (empty($currentPassword) || !password_verify($currentPassword, $user['password'])) {
            $errors[] = '当前密码不正确';
        }
        // 验证新密码长度
        if (strlen($newPassword) < 6) {
            $errors[] = '新密码长度不少于6位';
        }
        // 验证确认密码
        if ($newPassword !== $confirmPassword) {
            $errors[] = '新密码和确认密码不匹配';
        }
    }
    if (!empty($captcha)) {
        // 验证验证码
        if (!isset($_SESSION['captcha_code']) || strtolower($captcha) !== strtolower($_SESSION['captcha_code'])) {
            $errors[] = '验证码错误';
        }
    }

    // 执行更新
    if (empty($errors)) {
        $updateData = [
            ':email' => $email,
            ':phone' => $phone,
            ':id' => $userId
        ];
        
        $sql = 'UPDATE users SET email = :email, phone = :phone, updated_at = NOW() WHERE id = :id';
        
        // 如果需要更新密码
        if (!empty($newPassword)) {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = 'UPDATE users SET email = :email, phone = :phone, password = :password, updated_at = NOW() WHERE id = :id';
            $updateData[':password'] = $passwordHash;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateData);
        
        // 更新session中的邮箱信息
        $_SESSION['user']['email'] = $email;
        $success = '个人信息更新成功';
        
        // 重新获取用户信息
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

ob_start();
?>
<div class="container mt-5" style="max-width:800px;">
    <h2>个人中心</h2>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mt-3">
            <?php foreach ($errors as $error): ?>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success mt-3">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="card mt-4">
        <div class="card-header">
            <h5>基本信息</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="mb-3">
                    <label class="form-label">用户名</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    <small class="text-muted">用户名不可修改</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">邮箱 <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">手机号（可选）</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="11位数字，如：13800138000">
                </div>

                <div class="mb-3">
                    <label class="form-label">用户类型</label>
                    <input type="text" class="form-control" 
                           value="<?php 
                               $typeMap = [
                                   'ordinary' => '普通用户',
                                   'professional' => '专业学习者',
                                   'doctor' => '基层医生',
                                   'admin' => '管理员'
                               ];
                               echo htmlspecialchars($typeMap[$user['user_type']], ENT_QUOTES, 'UTF-8'); 
                           ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">注册时间</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($user['created_at'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-label">最后登录时间</label>
                    <input type="text" class="form-control" 
                           value="<?php echo htmlspecialchars($user['last_login'] ?? '未登录', ENT_QUOTES, 'UTF-8'); ?>" disabled>
                </div>

                <hr>
                <h5 class="mt-4">修改密码（可选）</h5>
                
                <div class="mb-3">
                    <label class="form-label">当前密码</label>
                    <input type="password" name="current_password" class="form-control" 
                           placeholder="输入当前密码以修改密码">
                </div>

                <div class="mb-3">
                    <label class="form-label">新密码</label>
                    <input type="password" name="new_password" class="form-control" 
                           placeholder="不少于6位，不修改请留空">
                </div>
                <div class="mb-3">
                    <label class="form-label">确认新密码</label>
                    <input type="password" name="confirm_password" class="form-control" 
                           placeholder="确认新密码">
                </div>
                <div class="mb-3">
                    <label class="form-label">验证码</label>
                    <div class= "d-flex gap-2">
                        <input type="text" name="captcha" class="form-control" required placeholder="请输入验证码">
                        <img src="captcha.php" alt="验证码" style="width:150px; height:50px; cursor:pointer;" onclick="this.src='captcha.php?rand='+Math.random()">
                    </div>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">保存修改</button>
                    <a href="index.php" class="btn btn-secondary ms-2">返回首页</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();
include 'base.php';
?>