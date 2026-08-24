<?php
$navActive = '';
$bodyPage = 'play';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/tmdb.php';

// 未登录检测 -> 跳转提示
if (!$currentUser || $currentUser['status'] != 1) {
    echo '<div class="main-container"><div class="need-login-tip" style="margin-top:30px;">
        <div class="need-login-icon"><i class="icon icon-user"></i></div>
        <div class="need-login-text">
            <h4>需要登录才可以观看哦</h4>
            <p>如没有账号请注册！登录后即可观看全部影视内容。</p>
        </div>
        <div>
            <button class="btn btn-secondary" data-login>登录</button>
            <button class="btn btn-primary" data-register style="margin-left:8px;">注册</button>
        </div>
    </div></div>';
    require_once __DIR__ . '/includes/footer.php';
    echo '<script>showToast("需要登录才可以观看哦，如没有账号请注册！","warning");</script>';
    exit;
}

$type = $_GET['type'] ?? 'movie';
$id = intval($_GET['id'] ?? 0);
$season = intval($_GET['season'] ?? 1);
$episode = intval($_GET['episode'] ?? 1);
$audio = $_GET['audio'] ?? 'original';
if (!$id) { echo '<script>location.href="/";</script>'; exit; }

$tmdb = new TMDB();
$detail = $type == 'tv' ? $tmdb->getTVDetail($id) : $tmdb->getMovieDetail($id);
$title = $detail['title'] ?? $detail['name'] ?? '';
$poster = $tmdb->getImageUrl($detail['poster_path'] ?? '', 'w500');
$backdrop = $tmdb->getImageUrl($detail['backdrop_path'] ?? '', 'original');
$rating = $detail['vote_average'] ?? 0;
$year = '';
if (!empty($detail['release_date'])) $year = substr($detail['release_date'], 0, 4);
if (!empty($detail['first_air_date'])) $year = substr($detail['first_air_date'], 0, 4);

// 获取播放源
$db = Database::getInstance();
$sources = $db->fetchAll("SELECT * FROM play_sources WHERE status = 1 ORDER BY sort ASC, id ASC");
if (empty($sources)) {
    $sources = [['id' => 0, 'name' => '云影资源', 'url' => 'https://api.yyzy-tv.vip/inc/apijson.php']];
}
$parseUrl = get_setting('player_parse_url', 'https://svip.ffzyplay.com/?url=');
$pageTitle = $title . ' - 在线播放';

$seasons = $detail['seasons'] ?? [];
$seasonDetail = null;
if ($type == 'tv' && !empty($seasons)) {
    $seasonDetail = $tmdb->getSeasonDetail($id, $season);
}
?>

<div style="margin:0 -20px;padding:0 0 30px;">
    <div class="detail-backdrop" style="position:absolute;inset:0;top:70px;height:400px;background-image:url('<?= $backdrop ?>');background-size:cover;background-position:center;opacity:0.2;"></div>
    <div style="position:absolute;top:470px;left:0;right:0;height:0;background:linear-gradient(180deg,transparent,var(--bg-dark));"></div>
</div>

<div style="position:relative;">
    <div class="player-container" id="playerContainer">
        <div id="playerPlaceholder" style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:20px;background:#000;">
            <div class="loading-spinner" style="width:48px;height:48px;border-width:4px;"></div>
            <div style="color:var(--text-muted);">正在加载播放器...</div>
        </div>
        <iframe id="playerIframe" style="display:none;width:100%;height:100%;border:0;" allowfullscreen allow="autoplay; encrypted-media"></iframe>
    </div>

    <!-- 播放源切换 -->
    <?php if (count($sources) > 1): ?>
    <div class="source-tabs">
        <span style="padding:8px 10px;color:var(--text-secondary);font-weight:600;font-size:14px;">播放源：</span>
        <?php foreach ($sources as $idx => $src): ?>
            <button class="source-tab <?= $idx == 0 ? 'active' : '' ?>" 
                data-url="<?= e($src['url']) ?>" data-name="<?= e($src['name']) ?>">
                <?= e($src['name']) ?>
            </button>
        <?php endforeach; ?>
        <?php if ($audio && $type == 'tv'): ?>
            <span style="margin-left:auto;padding:8px 10px;color:var(--text-secondary);font-size:13px;">
                当前配音：<span style="color:var(--theme-color);font-weight:600;"><?= $audio == 'mandarin' ? '普通话配音' : '原声' ?></span>
            </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 基本信息 -->
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);padding:24px;border:1px solid var(--border-color);margin-bottom:24px;">
        <h1 style="font-size:24px;font-weight:800;margin-bottom:10px;"><?= e($title) ?></h1>
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px;">
            <span class="detail-rating"><i class="icon icon-star"></i> <?= number_format($rating, 1) ?> 分</span>
            <?php if ($year): ?><span><?= $year ?></span><?php endif; ?>
            <span style="color:var(--text-secondary);"><?= $type == 'movie' ? '电影' : '剧集' ?></span>
            <?php if ($type == 'tv'): ?>
                <span style="color:var(--text-secondary);">第 <?= $season ?> 季 · 第 <?= $episode ?> 集</span>
            <?php endif; ?>
            <button class="btn btn-secondary btn-sm" onclick="toggleFav()"><i class="icon icon-heart-outline"></i> <span id="favBtnText">收藏</span></button>
            <button class="btn btn-secondary btn-sm" onclick="shareIt()"><i class="icon icon-feedback"></i> 分享</button>
        </div>
        <p style="color:var(--text-secondary);font-size:14px;line-height:1.8;"><?= e($detail['overview'] ?? '') ?></p>
    </div>

    <!-- 剧集选集（TV） -->
    <?php if ($type == 'tv' && $seasonDetail && !empty($seasonDetail['episodes'])): ?>
    <div style="background:var(--bg-card);border-radius:var(--radius-lg);padding:24px;border:1px solid var(--border-color);margin-bottom:24px;">
        <h3 style="font-size:18px;font-weight:700;margin-bottom:16px;">选集（共 <?= count($seasonDetail['episodes']) ?> 集）</h3>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <?php foreach ($seasonDetail['episodes'] as $ep):
                $en = $ep['episode_number'];
                $isCur = $en == $episode;
            ?>
            <a href="/play.php?type=tv&id=<?= $id ?>&season=<?= $season ?>&episode=<?= $en ?><?= $audio ? '&audio='.$audio : '' ?>"
               class="btn btn-sm" style="min-width:56px;<?= $isCur ? 'background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));color:#fff;' : 'background:var(--bg-input);color:var(--text-secondary);border:1px solid var(--border-color);' ?>">
                <?= $en ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
const __PLAY = {
    id: <?= $id ?>,
    type: '<?= $type ?>',
    title: <?= json_encode($title) ?>,
    poster: <?= json_encode($poster) ?>,
    season: <?= $season ?>,
    episode: <?= $episode ?>,
    sources: <?= json_encode($sources) ?>,
    parseUrl: <?= json_encode($parseUrl) ?>,
    currentSource: 0,
    timer: null
};

function buildPlayUrl(sourceIdx) {
    const src = __PLAY.sources[sourceIdx] || __PLAY.sources[0];
    const apiUrl = src.url;
    let kw = __PLAY.title;
    if (__PLAY.type === 'tv') kw = `${__PLAY.title} 第${__PLAY.season}季 第${__PLAY.episode}集`;
    // 优先通过 yyzy api 返回搜索结果获取直链
    const fullSrc = apiUrl + (apiUrl.includes('?') ? '&' : '?') + 'wd=' + encodeURIComponent(kw);
    // 拼接解析器
    return __PLAY.parseUrl + encodeURIComponent(fullSrc);
}

function switchSource(idx) {
    __PLAY.currentSource = idx;
    document.querySelectorAll('.source-tab').forEach((t, i) => t.classList.toggle('active', i === idx));
    const url = buildPlayUrl(idx);
    const iframe = document.getElementById('playerIframe');
    document.getElementById('playerPlaceholder').style.display = 'flex';
    iframe.style.display = 'none';
    setTimeout(() => {
        iframe.src = url;
        iframe.onload = () => {
            document.getElementById('playerPlaceholder').style.display = 'none';
            iframe.style.display = 'block';
        };
    }, 200);
    saveWatchProgress(true);
}

window.addEventListener('load', () => {
    setTimeout(() => switchSource(0), 300);
    startWatchTimer();
});

// 观看计时和进度
function startWatchTimer() {
    __PLAY.timer = setInterval(() => {
        fetch('/api/user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'update_watch_history',
                tmdb_id: __PLAY.id,
                type: __PLAY.type,
                season_number: __PLAY.season,
                episode_number: __PLAY.episode,
                title: __PLAY.title + (__PLAY.type === 'tv' ? ` S${__PLAY.season}E${__PLAY.episode}` : ''),
                poster: __PLAY.poster,
                watch_seconds: 5,
                last_position: 0
            })
        }).catch(() => {});
    }, 5000);
}
function saveWatchProgress(silent) {}
window.addEventListener('beforeunload', () => clearInterval(__PLAY.timer));

async function toggleFav() {
    const res = await apiRequest('/api/user.php', { action: 'toggle_favorite', tmdb_id: __PLAY.id, type: __PLAY.type, title: __PLAY.title, poster: __PLAY.poster });
    if (res.success) {
        const txt = document.getElementById('favBtnText');
        txt.textContent = res.favorited ? '已收藏' : '收藏';
        showToast(res.message, 'success');
    }
}

function shareIt() {
    const url = location.href;
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => showToast('链接已复制', 'success'));
    } else {
        showToast('分享链接：' + url, 'info', 5000);
    }
}

// 检查是否收藏
(async function() {
    const res = await fetch(`/api/user.php?action=check_favorite&tmdb_id=${__PLAY.id}&type=${__PLAY.type}`);
    const data = await res.json();
    if (data.success && data.favorited) document.getElementById('favBtnText').textContent = '已收藏';
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
