<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
@session_start();

$step = intval($_GET['step'] ?? 1);
$msg = '';

function testDb($cfg) {
    try {
        $pdo = new PDO("mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4", $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return $pdo;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = trim($_POST['db_host'] ?? 'localhost');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $cfg = ['host'=>$host,'name'=>$name,'user'=>$user,'pass'=>$pass];
    $pdo = testDb($cfg);

    if ($step == 1) {
        if (is_string($pdo)) {
            $msg = '❌ 数据库连接失败：' . htmlspecialchars($pdo) . '<br>请检查配置或尝试先手动创建数据库。';
        } else {
            // 写入 config.php
            $configContent = '<?php' . "\n"
                . '// 数据库配置' . "\n"
                . "define('DB_HOST', '" . addslashes($host) . "');\n"
                . "define('DB_NAME', '" . addslashes($name) . "');\n"
                . "define('DB_USER', '" . addslashes($user) . "');\n"
                . "define('DB_PASS', '" . addslashes($pass) . "');\n"
                . "\n"
                . '// SMTP邮件配置' . "\n"
                . "define('SMTP_HOST', 'smtp.163.com');\n"
                . "define('SMTP_PORT', 465);\n"
                . "define('SMTP_USER', 'jtxnb886@163.com');\n"
                . "define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');\n"
                . "define('SMTP_FROM', 'jtxnb886@163.com');\n"
                . "define('SMTP_FROM_NAME', 'Jay影视');\n"
                . "\n"
                . '// 网站基础配置' . "\n"
                . "define('SITE_URL', (isset(\$_SERVER['HTTPS']) ? 'https' : 'http') . '://' . \$_SERVER['HTTP_HOST']);\n"
                . "define('SITE_PATH', dirname(__FILE__));\n"
                . "\n"
                . "error_reporting(E_ALL);\n"
                . "ini_set('display_errors', 0);\n"
                . "\n"
                . "date_default_timezone_set('Asia/Shanghai');\n"
                . "\n"
                . "if (session_status() == PHP_SESSION_NONE) {\n"
                . "    session_start();\n"
                . "}\n"
                . "?>\n";
            $ok = @file_put_contents(dirname(__DIR__) . '/config.php', $configContent);
            if ($ok === false) {
                $msg = '⚠️ 连接成功但无法写入 config.php 文件，手动将以下内容复制到 config.php 后继续：<br>'
                    . '<pre style="background:#000;padding:12px;border-radius:8px;overflow:auto;margin-top:10px;font-size:12px;">' . htmlspecialchars($configContent) . '</pre>';
            } else {
                $_SESSION['INSTALLED_DB'] = true;
                header('Location: ?step=2');
                exit;
            }
        }
    }
    if ($step == 2) {
        if (!file_exists(dirname(__DIR__) . '/config.php')) {
            header('Location: install/index.php'); exit;
        }
        require dirname(__DIR__) . '/config.php';
        try {
            $pdo = new PDO("mysql:host=".DB_HOST.";charset=utf8mb4", DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            // 执行SQL
            $sql = file_get_contents(__DIR__ . '/database.sql');
            if (!$sql) { $msg = '找不到 database.sql 文件'; }
            else {
                // 分片执行
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $pdo->exec("USE `".DB_NAME."`;");
                $queries = array_filter(array_map('trim', explode(";\n", $sql)));
                foreach ($queries as $q) {
                    $q = trim($q);
                    if (empty($q) || strpos($q, '--') === 0 || strpos($q, '/*') === 0 || strpos($q, 'SET ') === 0 || strpos($q, 'START ') === 0 || strpos($q, 'COMMIT') === 0 || strpos($q, 'CREATE DATABASE') === 0 || strpos($q, 'USE ') === 0) continue;
                    try { $pdo->exec($q); } catch (Exception $e) { /* ignore duplicate */ }
                }
                $_SESSION['INSTALLED_SQL'] = true;
                header('Location: ?step=3');
                exit;
            }
        } catch (Exception $e) {
            $msg = '❌ 安装出错：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<title>Jay影视 安装向导</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'PingFang SC','Microsoft YaHei',sans-serif;background:linear-gradient(135deg,#0a0a0f 0%,#1e1b4b 100%);min-height:100vh;color:#fff;display:flex;align-items:center;justify-content:center;padding:20px}
.wrap{width:100%;max-width:680px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:20px;backdrop-filter:blur(20px);box-shadow:0 20px 60px rgba(0,0,0,0.5);overflow:hidden}
.logo{padding:36px 36px 24px;text-align:center;background:linear-gradient(135deg,#6366f1,#8b5cf6)}
.logo h1{font-size:30px;font-weight:800;display:flex;align-items:center;justify-content:center;gap:12px}
.logo span.icon{display:inline-block;width:42px;height:42px;background:#fff;color:#6366f1;border-radius:10px;font-weight:900;font-size:22px;line-height:42px;letter-spacing:0;box-shadow:0 4px 12px rgba(0,0,0,0.2)}
.logo p{margin-top:8px;opacity:0.9;font-size:14px}
.steps{display:flex;gap:8px;padding:24px 36px 8px;border-bottom:1px solid rgba(255,255,255,0.08)}
.step{flex:1;padding:10px;border-radius:10px;background:rgba(255,255,255,0.04);text-align:center;font-size:13px;font-weight:600;color:rgba(255,255,255,0.5)}
.step.active{background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;box-shadow:0 4px 15px rgba(99,102,241,0.4)}
.step.done{background:rgba(16,185,129,0.2);color:#10b981;border:1px solid rgba(16,185,129,0.3)}
.body{padding:32px 36px}
.msg{padding:14px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);border-radius:10px;margin-bottom:20px;line-height:1.7;font-size:13px}
h2{font-size:20px;margin-bottom:18px}
.form-group{margin-bottom:16px}
label{display:block;font-size:13px;font-weight:600;color:#cbd5e1;margin-bottom:8px}
input{width:100%;height:46px;padding:0 14px;background:rgba(255,255,255,0.05);border:1.5px solid rgba(255,255,255,0.1);border-radius:10px;color:#fff;font-size:14px;outline:none;transition:all .2s;font-family:inherit}
input:focus{border-color:#6366f1;box-shadow:0 0 0 4px rgba(99,102,241,0.2)}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.hint{color:#94a3b8;font-size:12px;margin-top:6px;line-height:1.7}
.btn{display:inline-flex;align-items:center;justify-content:center;padding:13px 28px;background:linear-gradient(135deg,#6366f1,#8b5cf6);color:#fff;font-weight:700;border-radius:10px;border:none;cursor:pointer;font-size:15px;box-shadow:0 6px 20px rgba(99,102,241,0.4);transition:all .2s;font-family:inherit}
.btn:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(99,102,241,0.5)}
.btn.ghost{background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.15);box-shadow:none}
.actions{display:flex;justify-content:space-between;margin-top:24px}
.admin-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:18px;margin-bottom:16px}
.admin-card h3{font-size:15px;margin-bottom:10px;color:#fbbf24;display:flex;align-items:center;gap:8px}
.admin-card p{color:#cbd5e1;font-size:13px;line-height:2}
.admin-card code{background:#000;padding:2px 8px;border-radius:4px;color:#10b981;font-family:monospace}
.success-box{padding:40px;text-align:center}
.success-icon{width:80px;height:80px;margin:0 auto 20px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;font-size:40px;box-shadow:0 10px 30px rgba(16,185,129,0.3)}
</style>
</head>
<body>
<div class="wrap">
    <div class="logo">
        <h1><span class="icon">J</span> Jay影视 安装向导</h1>
        <p>轻松搭建您的在线影视平台</p>
    </div>
    <div class="steps">
        <div class="step <?= $step==1?'active':($step>1?'done':'') ?>">① 数据库配置</div>
        <div class="step <?= $step==2?'active':($step>2?'done':'') ?>">② 导入数据</div>
        <div class="step <?= $step==3?'active':'' ?>">③ 完成安装</div>
    </div>
    <div class="body">

<?php if ($step == 1): ?>
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    <h2>🔧 数据库配置信息</h2>
    <form method="post" action="?step=1">
        <div class="grid">
            <div class="form-group">
                <label>数据库主机</label>
                <input type="text" name="db_host" value="localhost" required>
            </div>
            <div class="form-group">
                <label>数据库名称</label>
                <input type="text" name="db_name" value="jay_movie" required>
                <div class="hint">如果数据库不存在会尝试自动创建</div>
            </div>
        </div>
        <div class="grid">
            <div class="form-group">
                <label>数据库用户名</label>
                <input type="text" name="db_user" value="root" required>
            </div>
            <div class="form-group">
                <label>数据库密码</label>
                <input type="password" name="db_pass" value="">
                <div class="hint">空密码留空即可</div>
            </div>
        </div>
        <div class="actions">
            <div style="align-self:center;color:#94a3b8;font-size:13px;">💡 配置信息保存在 config.php，可随时修改</div>
            <button class="btn" type="submit">测试连接并下一步 →</button>
        </div>
    </form>

<?php elseif ($step == 2): ?>
    <?php if ($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    <h2>🗄️ 正在导入数据表</h2>
    <p style="color:#cbd5e1;line-height:1.8;margin-bottom:20px;">点击下方按钮即可导入所有数据表、默认管理员账号、默认播放源和系统配置。</p>
    <div class="admin-card">
        <h3>🔐 默认管理员账号</h3>
        <p>用户名：<code>杰同学</code><br>密码：<code>101113</code><br>登录后可进入管理后台 /admin/ 管理网站</p>
    </div>
    <div class="admin-card" style="border-color:rgba(251,191,36,0.2);background:rgba(251,191,36,0.05);">
        <h3>📧 邮件服务已默认配置</h3>
        <p>SMTP Host：<code>smtp.163.com</code> (SSL 465)<br>发件账号：<code>jtxnb886@163.com</code><br>发件名称：<code>Jay影视</code></p>
    </div>
    <form method="post" action="?step=2">
        <div class="actions">
            <a href="?step=1" class="btn ghost">← 上一步</a>
            <button class="btn" type="submit">导入数据库并继续 →</button>
        </div>
    </form>

<?php elseif ($step == 3): ?>
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h2 style="margin-bottom:12px;">🎉 恭喜安装完成！</h2>
        <p style="color:#94a3b8;margin-bottom:20px;line-height:1.8;">Jay影视系统已成功安装，现在您可以开始使用了！<br>请尽快删除 install 文件夹，以防他人再次运行安装程序。</p>
        <div class="admin-card" style="text-align:left;margin:24px 0;">
            <h3>👤 管理员账号</h3>
            <p>用户名：<code>杰同学</code><br>密码：<code>101113</code><br>管理后台：<a href="/admin/" style="color:#6366f1;text-decoration:underline;">/admin/</a></p>
        </div>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="/" class="btn">🏠 进入首页</a>
            <a href="/admin/" class="btn ghost">⚙️ 进入后台</a>
        </div>
    </div>
<?php endif; ?>

    </div>
</div>
</body>
</html>
