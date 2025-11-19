<?php
include 'config.php';
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
ob_start();
?>
<div class="container mt-5" style="max-width:600px;">
    <div class="alert alert-success mt-5">您已成功退出登录，正在跳转到首页...</div>
</div>
<?php
$pageContent = ob_get_clean();
include 'base.php';
header('Refresh:2;url=index.php');
exit;