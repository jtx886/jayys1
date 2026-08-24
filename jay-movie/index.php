<?php
$navActive = 'home';
$pageTitle = '首页 - 免费在线观看高清电影电视剧';
$bodyPage = 'home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/tmdb.php';

$tmdb = new TMDB();
$trending = $tmdb->getTrending('all', 'day', 1) ?: ['results' => []];
$nowPlaying = $tmdb->getNowPlaying(1) ?: ['results' => []];
$popularMovies = $tmdb->getPopularMovies(1) ?: ['results' => []];
$popularTV = $tmdb->getPopularTV(1) ?: ['results' => []];
$anime = $tmdb->getAnime(1) ?: ['results' => []];
$variety = $tmdb->getVariety(1) ?: ['results' => []];

function renderPoster($item, $type = null) {
    global $tmdb;
    $t = $type ?: ($item['media_type'] ?? (isset($item['title']) ? 'movie' : 'tv'));
    $id = $item['id'];
    $title = $item['title'] ?? $item['name'] ?? '';
    $poster = $tmdb->getImageUrl($item['poster_path'] ?? '', 'w500');
    $rating = $item['vote_average'] ?? 0;
    $year = '';
    if (!empty($item['release_date'])) $year = substr($item['release_date'], 0, 4);
    if (!empty($item['first_air_date'])) $year = substr($item['first_air_date'], 0, 4);
    $typeText = $t == 'movie' ? '电影' : '剧集';
    $epInfo = '';
    if ($t == 'tv' && isset($item['episode_count'])) $epInfo = '· 更新至' . $item['episode_count'] . '集';
    elseif ($t == 'tv' && isset($item['number_of_episodes'])) $epInfo = '· ' . $item['number_of_episodes'] . '集全';
    $url = "/detail.php?type=$t&id=$id";
    $onClick = "onclick=\"requireLogin(function(){location.href='$url';})\"";
    return <<<HTML
    <div class="movie-card" $onClick>
        <div class="movie-poster">
            <img src="$poster" alt="{$title}" loading="lazy" onerror="this.style.opacity=0;this.parentNode.innerHTML='<div style=\\'width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:var(--bg-input);color:var(--text-muted);font-size:14px;\\'>暂无封面</div>'">
            <div class="movie-rating"><i class="icon icon-star"></i> " . number_format($rating, 1) . "</div>
        </div>
        <div class="movie-info">
            <div class="movie-title">$title</div>
            <div class="movie-subtitle"><span>$year</span><span>·</span><span>$typeText $epInfo</span></div>
        </div>
    </div>
    HTML;
}
?>

<!-- Hero Slider -->
<?php if (!empty($nowPlaying['results'])): ?>
<section class="hero-section" id="heroSection">
    <?php
    $bannerItems = array_slice($nowPlaying['results'], 0, 3);
    foreach ($bannerItems as $idx => $m):
        $backdrop = $tmdb->getImageUrl($m['backdrop_path'] ?? '', 'original');
        $poster = $tmdb->getImageUrl($m['poster_path'] ?? '', 'w500');
        $title = $m['title'] ?? $m['name'] ?? '';
        $year = !empty($m['release_date']) ? substr($m['release_date'], 0, 4) : '';
        $rating = $m['vote_average'] ?? 0;
        $overview = $m['overview'] ?? '';
        $t = isset($m['title']) ? 'movie' : 'tv';
        $id = $m['id'];
        $genres = [];
        if (!empty($m['genre_ids'])) {
            $genreNames = ['28'=>'动作','12'=>'冒险','16'=>'动画','35'=>'喜剧','80'=>'犯罪','99'=>'纪录','18'=>'剧情','10751'=>'家庭','14'=>'奇幻','36'=>'历史','27'=>'恐怖','10402'=>'音乐','9648'=>'悬疑','10749'=>'爱情','878'=>'科幻','10770'=>'电视','53'=>'惊悚','10752'=>'战争','37'=>'西部','10759'=>'动作冒险','10762'=>'儿童','10763'=>'新闻','10764'=>'真人秀','10765'=>'科幻奇幻','10766'=>'肥皂','10767'=>'脱口秀','10768'=>'战争政治','37'=>'西部'];
            foreach ($m['genre_ids'] as $gid) if (isset($genreNames[$gid])) $genres[] = $genreNames[$gid];
        }
        $genreText = implode(' / ', array_slice($genres, 0, 3));
        $playUrl = "/play.php?type=$t&id=$id";
    ?>
    <div class="hero-slide" style="<?= $idx > 0 ? 'display:none;' : '' ?>" data-index="<?= $idx ?>">
        <?php if ($backdrop): ?>
            <div class="hero-bg" style="background-image:url('<?= $backdrop ?>')"></div>
        <?php endif; ?>
        <div class="hero-content">
            <span class="hero-badge"><?= $idx == 0 ? '🔥 正在热映' : '⭐ 精选推荐' ?></span>
            <h1 class="hero-title"><?= e($title) ?></h1>
            <div class="hero-meta">
                <span class="hero-rating"><i class="icon icon-star"></i> <?= number_format($rating, 1) ?></span>
                <span><?= $year ?></span>
                <?php if ($genreText): ?><span><?= e($genreText) ?></span><?php endif; ?>
            </div>
            <p class="hero-desc"><?= e($overview) ?></p>
            <div class="hero-actions">
                <button class="btn btn-primary btn-lg" onclick="requireLogin(function(){location.href='<?= $playUrl ?>';})"><i class="icon icon-play"></i> 立即播放</button>
                <button class="btn btn-secondary btn-lg" style="background:rgba(255,255,255,0.08);backdrop-filter:blur(10px);border-color:rgba(255,255,255,0.15);"
                    onclick="requireLogin(function(){document.dispatchEvent(new CustomEvent('jay:addFavorite',{detail:{id:<?= $id ?>,type:'<?= $t ?>',title:<?= json_encode($title) ?>,poster:<?= json_encode($poster) ?>}}));})">
                    <i class="icon icon-plus"></i> 收藏
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="hero-dots">
        <?php foreach ($bannerItems as $idx => $m): ?>
            <span class="hero-dot <?= $idx == 0 ? 'active' : '' ?>" onclick="switchHero(<?= $idx ?>)"></span>
        <?php endforeach; ?>
    </div>
</section>
<script>
let heroIdx = 0;
function switchHero(idx) {
    document.querySelectorAll('#heroSection .hero-slide').forEach((s, i) => {
        s.style.display = i === idx ? 'flex' : 'none';
    });
    document.querySelectorAll('#heroSection .hero-dot').forEach((d, i) => {
        d.classList.toggle('active', i === idx);
    });
    heroIdx = idx;
}
setInterval(() => {
    const total = document.querySelectorAll('#heroSection .hero-slide').length;
    if (total > 1) switchHero((heroIdx + 1) % total);
}, 6000);
document.addEventListener('jay:addFavorite', (e) => {
    const d = e.detail;
    fetch('/api/user.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action:'toggle_favorite', tmdb_id: d.id, type: d.type, title: d.title, poster: d.poster})
    }).then(r => r.json()).then(res => {
        if (res.success) showToast(res.message, res.favorited ? 'success' : 'info');
        else if (res.need_login) showToast(res.message, 'warning');
    });
});
</script>
<?php endif; ?>

<!-- 热门推荐 -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon"><i class="icon icon-star"></i></span>
            热门推荐
        </h2>
        <a class="see-more" href="/search.php?q=%E7%83%AD%E9%97%A8">查看更多 <i class="icon icon-chevron-right"></i></a>
    </div>
    <div class="horizontal-scroll-wrapper">
        <button class="scroll-btn prev"><i class="icon icon-chevron-left"></i></button>
        <button class="scroll-btn next"><i class="icon icon-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php
            $combined = [];
            if (!empty($trending['results'])) $combined = array_merge($combined, $trending['results']);
            if (!empty($nowPlaying['results'])) $combined = array_merge($combined, $nowPlaying['results']);
            $seen = []; $combinedFiltered = [];
            foreach ($combined as $c) { $key = ($c['media_type'] ?? 'x') . '_' . $c['id']; if (!isset($seen[$key])) { $seen[$key] = true; $combinedFiltered[] = $c; } }
            foreach (array_slice($combinedFiltered, 0, 18) as $m) echo renderPoster($m);
            ?>
        </div>
    </div>
</section>

<!-- 电影分类 -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon"><i class="icon icon-play"></i></span>
            电影
        </h2>
        <a class="see-more" href="/category.php?type=movie">查看更多 <i class="icon icon-chevron-right"></i></a>
    </div>
    <div class="category-tabs">
        <span class="category-tab active" data-type="movie" data-genre="0">全部</span>
        <span class="category-tab" data-type="movie" data-genre="28">动作</span>
        <span class="category-tab" data-type="movie" data-genre="35">喜剧</span>
        <span class="category-tab" data-type="movie" data-genre="10749">爱情</span>
        <span class="category-tab" data-type="movie" data-genre="878">科幻</span>
        <span class="category-tab" data-type="movie" data-genre="53">悬疑</span>
        <span class="category-tab" data-type="movie" data-genre="18">剧情</span>
    </div>
    <div class="movie-grid" id="movieGrid">
        <?php foreach (array_slice($popularMovies['results'] ?? [], 0, 12) as $m) echo renderPoster($m, 'movie'); ?>
    </div>
</section>

<!-- 电视剧 -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon"><i class="icon icon-play"></i></span>
            电视剧
        </h2>
        <a class="see-more" href="/category.php?type=tv">查看更多 <i class="icon icon-chevron-right"></i></a>
    </div>
    <div class="horizontal-scroll-wrapper">
        <button class="scroll-btn prev"><i class="icon icon-chevron-left"></i></button>
        <button class="scroll-btn next"><i class="icon icon-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php foreach (array_slice($popularTV['results'] ?? [], 0, 18) as $t) echo renderPoster($t, 'tv'); ?>
        </div>
    </div>
</section>

<!-- 动漫 -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon" style="background:linear-gradient(135deg,#ec4899,#be185d);"><i class="icon icon-star"></i></span>
            动漫
        </h2>
        <a class="see-more" href="/category.php?type=tv&genre=16">查看更多 <i class="icon icon-chevron-right"></i></a>
    </div>
    <div class="movie-grid">
        <?php foreach (array_slice($anime['results'] ?? [], 0, 12) as $a) echo renderPoster($a, 'tv'); ?>
    </div>
</section>

<!-- 综艺 -->
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="icon icon-feedback"></i></span>
            综艺
        </h2>
        <a class="see-more" href="/category.php?type=tv&genre=10764">查看更多 <i class="icon icon-chevron-right"></i></a>
    </div>
    <div class="horizontal-scroll-wrapper">
        <button class="scroll-btn prev"><i class="icon icon-chevron-left"></i></button>
        <button class="scroll-btn next"><i class="icon icon-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php foreach (array_slice($variety['results'] ?? [], 0, 18) as $v) echo renderPoster($v, 'tv'); ?>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', async function() {
        const parent = this.parentNode;
        parent.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        const type = this.dataset.type;
        const genre = this.dataset.genre;
        const grid = document.getElementById('movieGrid');
        grid.style.opacity = '0.4';
        try {
            const res = await fetch(`/api/tmdb.php?action=category&type=${type}&genre=${genre}`);
            const data = await res.json();
            if (data.success) {
                let html = '';
                data.results.slice(0, 12).forEach(item => {
                    const title = item.title || item.name || '';
                    const poster = item.poster_path ? `https://image.tmdb.org/t/p/w500${item.poster_path}` : '';
                    const rating = item.vote_average ? item.vote_average.toFixed(1) : 0;
                    const year = (item.release_date || item.first_air_date || '').substring(0, 4);
                    const url = `/detail.php?type=${type}&id=${item.id}`;
                    const typeText = type == 'movie' ? '电影' : '剧集';
                    html += `<div class="movie-card" onclick="requireLogin(function(){location.href='${url}';})">
                        <div class="movie-poster">
                            <img src="${poster}" loading="lazy" onerror="this.style.opacity=0">
                            <div class="movie-rating"><i class="icon icon-star"></i> ${rating}</div>
                        </div>
                        <div class="movie-info">
                            <div class="movie-title">${escapeHtml(title)}</div>
                            <div class="movie-subtitle"><span>${year}</span><span>·</span><span>${typeText}</span></div>
                        </div>
                    </div>`;
                });
                if (!html) html = '<div style="grid-column:1/-1;padding:60px;text-align:center;color:var(--text-muted);">暂无内容</div>';
                grid.innerHTML = html;
            }
        } catch (e) {}
        grid.style.opacity = '1';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
