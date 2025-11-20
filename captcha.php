<?php
// 引入Composer自动加载文件
require __DIR__ . '/vendor/autoload.php';

// 引入验证码类
use Minho\Captcha\CaptchaBuilder;

// 初始化Session（用于存储验证码文本，后续验证需要）
session_start();

// 创建验证码实例
$captcha = new CaptchaBuilder();

// 配置验证码参数（可选，默认有默认值）
$captcha->initialize([
    'width' => 150,    // 图片宽度
    'height' => 50,    // 图片高度
    'line' => false,   // 是否添加直线干扰
    'curve' => true,   // 是否添加曲线干扰
    'noise' => 1,      // 是否添加噪点（1为启用，0为禁用）
    'fonts' => []      // 字体文件路径数组（默认使用库自带字体）
]);

// 生成验证码图片
$captcha->create();

// 将验证码文本存入Session（用于后续验证用户输入）
$_SESSION['captcha_code'] = $captcha->getText();

// 输出验证码图片（直接在浏览器显示）
$captcha->output(1); // 参数为图片质量（1-9）