<?php require_once dirname(__FILE__) . '/../includes/functions.php';
$currentUser = current_user();
$navActive = $navActive ?? 'home';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<title><?= isset($pageTitle) ? e($pageTitle) . ' - ' : '' ?><?= defined('SITE_NAME') ? SITE_NAME : 'Jay影视' ?></title>
<meta name="description" content="Jay影视 - 免费在线观看高清电影、电视剧、动漫、综艺">
<link rel="stylesheet" href="/assets/css/style.css?v=20240101">
<script>
    window.__THEME_COLOR = '<?= addslashes(defined('THEME_COLOR') ? THEME_COLOR : '#6366f1') ?>';
    window.__USER = <?= json_encode($currentUser ? [
        'id' => $currentUser['id'],
        'username' => $currentUser['username'],
        'email' => $currentUser['email'],
        'avatar' => $currentUser['avatar'],
        'is_admin' => $currentUser['is_admin'] == 1,
        'status' => $currentUser['status']
    ] : null) ?>;
</script>
</head>
<body data-page="<?= $bodyPage ?? 'home' ?>" style="<?= isset($bodyBg) ? $bodyBg : '' ?>">

<header class="site-header">
    <div class="header-container">
        <button class="mobile-menu-btn" aria-label="menu"><i class="icon icon-menu"></i></button>
        <a href="/" class="jay-logo" style="position:relative;display:inline-flex;align-items:center;gap:8px;">
            <span class="logo-wrapper">
                <span style="display:inline-block;width:34px;height:34px;background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));border-radius:9px;position:relative;box-shadow:0 3px 12px var(--theme-color);">
                    <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px;line-height:1;">J</span>
                </span>
            </span>
            <span><?= defined('SITE_NAME') ? SITE_NAME : 'Jay影视' ?></span>
        </a>

        <nav class="nav-links">
            <a class="nav-link <?= $navActive == 'home' ? 'active' : '' ?>" href="/"><i class="icon icon-home"></i>首页</a>
            <a class="nav-link <?= $navActive == 'movie' ? 'active' : '' ?>" href="/category.php?type=movie"><i class="icon icon-play"></i>电影</a>
            <a class="nav-link <?= $navActive == 'tv' ? 'active' : '' ?>" href="/category.php?type=tv"><i class="icon icon-play"></i>电视剧</a>
            <a class="nav-link <?= $navActive == 'anime' ? 'active' : '' ?>" href="/category.php?type=tv&genre=16"><i class="icon icon-star"></i>动漫</a>
            <a class="nav-link <?= $navActive == 'variety' ? 'active' : '' ?>" href="/category.php?type=tv&genre=10764"><i class="icon icon-feedback"></i>综艺</a>
            <a class="nav-link <?= $navActive == 'feedback' ? 'active' : '' ?>" href="/feedback.php"><i class="icon icon-feedback"></i>反馈</a>
        </nav>

        <div class="search-box">
            <i class="icon icon-search search-icon"></i>
            <input type="text" class="search-input" placeholder="搜索电影、电视剧、动漫..." value="<?= e($_GET['q'] ?? '') ?>">
        </div>

        <div class="header-actions">
            <?php if ($currentUser): ?>
                <button class="btn btn-ghost" style="width:42px;height:42px;padding:0;border-radius:50%;" onclick="location.href='/profile.php'" title="观看历史">
                    <i class="icon icon-clock" style="font-size:18px;"></i>
                </button>
                <div class="user-menu">
                    <div class="user-avatar" onclick="document.querySelector('.user-dropdown').classList.toggle('show')">
                        <?php if (!empty($currentUser['avatar'])): ?>
                            <img src="<?= e($currentUser['avatar']) ?>" alt="avatar">
                        <?php else: ?>
                            <?= mb_substr($currentUser['username'], 0, 1, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-dropdown">
                        <div class="user-dropdown-item" style="pointer-events:none;">
                            <div>
                                <div style="font-weight:600;color:#fff;display:flex;align-items:center;flex-wrap:wrap;gap:6px;">
                                    <?= e($currentUser['username']) ?>
                                    <?php if ($currentUser['is_admin']): ?>
                                        <span class="admin-badge"><span class="admin-badge-icon"></span>开发者</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:12px;margin-top:2px;"><?= e($currentUser['email']) ?></div>
                            </div>
                        </div>
                        <div class="user-dropdown-divider"></div>
                        <a class="user-dropdown-item" href="/profile.php"><i class="icon icon-user"></i>个人中心</a>
                        <a class="user-dropdown-item" href="/profile.php?tab=favorites"><i class="icon icon-heart-outline"></i>我的收藏</a>
                        <a class="user-dropdown-item" href="/profile.php?tab=history"><i class="icon icon-clock"></i>观看历史</a>
                        <?php if ($currentUser['is_admin']): ?>
                        <div class="user-dropdown-divider"></div>
                        <a class="user-dropdown-item admin" href="/admin/"><i class="icon icon-dashboard"></i>管理后台</a>
                        <?php endif; ?>
                        <div class="user-dropdown-divider"></div>
                        <a class="user-dropdown-item" href="javascript:void(0);" data-logout><i class="icon icon-x"></i>退出登录</a>
                    </div>
                </div>
            <?php else: ?>
                <button class="btn btn-secondary btn-sm" data-login>登录</button>
                <button class="btn btn-primary btn-sm" data-register>注册</button>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="main-container" id="mainContainer">
