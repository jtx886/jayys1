</main>

<footer class="site-footer">
    <div class="footer-container">
        <div>
            <a href="/" class="jay-logo" style="color:#fff;display:inline-flex;align-items:center;gap:8px;">
                <span style="display:inline-block;width:34px;height:34px;background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));border-radius:9px;position:relative;">
                    <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:18px;line-height:1;">J</span>
                </span>
                <span><?= defined('SITE_NAME') ? SITE_NAME : 'Jay影视' ?></span>
            </a>
            <p class="footer-brand-desc">
                汇集全球热门影视资源，海量电影、电视剧、动漫、综艺免费在线观看。高清画质，极速加载，给您最舒适的观影体验。
            </p>
        </div>
        <div>
            <h4 class="footer-title">快速导航</h4>
            <div class="footer-links">
                <a class="footer-link" href="/">首页</a>
                <a class="footer-link" href="/category.php?type=movie">电影</a>
                <a class="footer-link" href="/category.php?type=tv">电视剧</a>
                <a class="footer-link" href="/category.php?type=tv&genre=16">动漫</a>
                <a class="footer-link" href="/category.php?type=tv&genre=10764">综艺</a>
            </div>
        </div>
        <div>
            <h4 class="footer-title">用户服务</h4>
            <div class="footer-links">
                <a class="footer-link" href="/feedback.php">意见反馈</a>
                <a class="footer-link" href="/profile.php">个人中心</a>
                <a class="footer-link" href="/profile.php?tab=favorites">我的收藏</a>
                <a class="footer-link" href="/profile.php?tab=history">观看历史</a>
            </div>
        </div>
        <div>
            <h4 class="footer-title">关于我们</h4>
            <div class="footer-links">
                <span class="footer-link">站长：杰同学</span>
                <span class="footer-link">内容来源于网络</span>
                <span class="footer-link">仅供学习交流</span>
                <span class="footer-link">© <?= date('Y') ?> Jay影视</span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div><?= defined('SITE_NAME') ? SITE_NAME : 'Jay影视' ?> · 免费在线影视资源 · All Rights Reserved</div>
    </div>
</footer>

<nav class="mobile-bottom-nav">
    <div class="mobile-nav-items">
        <a class="mobile-nav-item <?= $navActive == 'home' ? 'active' : '' ?>" href="/">
            <i class="icon icon-home"></i><span>首页</span>
        </a>
        <a class="mobile-nav-item <?= $navActive == 'category' ? 'active' : '' ?>" href="/search.php">
            <i class="icon icon-search"></i><span>搜索</span>
        </a>
        <a class="mobile-nav-item <?= $navActive == 'feedback' ? 'active' : '' ?>" href="/feedback.php">
            <i class="icon icon-feedback"></i><span>反馈</span>
        </a>
        <a class="mobile-nav-item <?= $navActive == 'profile' ? 'active' : '' ?>" href="<?= $currentUser ? '/profile.php' : 'javascript:void(0);' ?>" onclick="if(!window.__USER) {event.preventDefault(); openAuthModal('login');}">
            <i class="icon icon-user"></i><span>我的</span>
        </a>
    </div>
</nav>

<script src="/assets/js/main.js?v=20240101"></script>
</body>
</html>
