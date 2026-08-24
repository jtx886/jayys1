<?php
$navActive = 'category';
$q = trim($_GET['q'] ?? '');
$pageTitle = ($q ? '搜索: ' . $q : '搜索') . ' - Jay影视';
$bodyPage = 'search';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/tmdb.php';

$tmdb = new TMDB();
$page = max(1, intval($_GET['page'] ?? 1));
$results = [];
$total = 0;
$totalPages = 1;
if ($q) {
    $res = $tmdb->search($q, $page, 'multi');
    $results = $res['results'] ?? [];
    $total = $res['total_results'] ?? 0;
    $totalPages = $res['total_pages'] ?? 1;
    if ($totalPages > 500) $totalPages = 500;
}
?>

<div class="section">
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);padding:30px;border:1px solid var(--border-color);margin-bottom:30px;">
        <form onsubmit="location.href='/search.php?q='+encodeURIComponent(this.kw.value);return false;" style="display:flex;gap:12px;flex-wrap:wrap;">
            <input type="text" name="kw" class="form-input" style="flex:1;min-width:240px;" placeholder="输入电影/电视剧/动漫/综艺名称搜索..." value="<?= e($q) ?>">
            <button class="btn btn-primary" type="submit" style="height:46px;padding:0 28px;"><i class="icon icon-search"></i> 搜索</button>
        </form>
        <?php if ($q): ?>
        <div style="margin-top:16px;color:var(--text-secondary);font-size:14px;">
            搜索 "<span style="color:var(--theme-color);font-weight:600;"><?= e($q) ?></span>" 共找到约 <b style="color:#fff;"><?= number_format($total) ?></b> 条结果
        </div>
        <?php endif; ?>
    </div>

    <?php if (!$q): ?>
    <div class="empty-state">
        <i class="icon icon-search" style="font-size:80px;"></i>
        <h3>输入关键词开始搜索</h3>
        <p style="color:var(--text-muted);font-size:14px;">支持搜索电影、电视剧、动漫、综艺等</p>
        <div style="margin-top:20px;">
            <span style="color:var(--text-muted);font-size:13px;margin-right:10px;">热门搜索：</span>
            <?php
            $hot = ['流浪地球', '庆余年', '海贼王', '三体', '黑神话', '鬼灭之刃', '长津湖', '狂飙'];
            foreach ($hot as $k) {
                echo "<a href='/search.php?q=".urlencode($k)."' class='category-tab' style='margin:3px 6px 3px 0;display:inline-block;'>$k</a>";
            }
            ?>
        </div>
    </div>
    <?php else: ?>

    <div class="movie-grid">
        <?php
        foreach ($results as $m) {
            $t = $m['media_type'] ?? (isset($m['title']) ? 'movie' : 'tv');
            if ($t == 'person') continue;
            $title = $m['title'] ?? $m['name'] ?? '';
            $poster = $tmdb->getImageUrl($m['poster_path'] ?? '', 'w500');
            $rating = $m['vote_average'] ?? 0;
            $y = '';
            if (!empty($m['release_date'])) $y = substr($m['release_date'], 0, 4);
            if (!empty($m['first_air_date'])) $y = substr($m['first_air_date'], 0, 4);
            $typeText = $t == 'movie' ? '电影' : '剧集';
            $url = "/detail.php?type=$t&id={$m['id']}";
            $r = number_format($rating, 1);
            echo <<<HTML
            <div class="movie-card" onclick="requireLogin(function(){location.href='$url';})">
                <div class="movie-poster">
                    <img src="$poster" loading="lazy" onerror="this.style.opacity=0">
                    <div class="movie-rating"><i class="icon icon-star"></i> $r</div>
                </div>
                <div class="movie-info">
                    <div class="movie-title">$title</div>
                    <div class="movie-subtitle"><span>$y</span><span>·</span><span>$typeText</span></div>
                </div>
            </div>
            HTML;
        }
        if (empty($results)) {
            echo '<div style="grid-column:1/-1;padding:80px;text-align:center;color:var(--text-muted);">
                <i class="icon icon-search" style="font-size:60px;display:block;margin-bottom:16px;"></i>
                <h3 style="color:var(--text-secondary);margin-bottom:8px;">没有找到相关结果</h3>
                <p>试试其他关键词吧</p>
            </div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="page-btn" href="/search.php?q=<?= urlencode($q) ?>&page=<?= $page - 1 ?>"><i class="icon icon-chevron-left"></i></a>
        <?php else: ?>
            <button class="page-btn" disabled><i class="icon icon-chevron-left"></i></button>
        <?php endif;
        $start = max(1, $page - 4);
        $end = min($totalPages, $start + 8);
        if ($end - $start < 8) $start = max(1, $end - 8);
        for ($i = $start; $i <= $end; $i++): ?>
            <a class="page-btn <?= $i == $page ? 'active' : '' ?>" href="/search.php?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endfor;
        if ($page < $totalPages): ?>
            <a class="page-btn" href="/search.php?q=<?= urlencode($q) ?>&page=<?= $page + 1 ?>"><i class="icon icon-chevron-right"></i></a>
        <?php else: ?>
            <button class="page-btn" disabled><i class="icon icon-chevron-right"></i></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
