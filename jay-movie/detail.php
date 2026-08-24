<?php
$navActive = '';
$bodyPage = 'detail';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/tmdb.php';

$type = $_GET['type'] ?? 'movie';
$id = intval($_GET['id'] ?? 0);
if (!$id) { echo '<script>location.href="/";</script>'; exit; }

$tmdb = new TMDB();
$detail = $type == 'tv' ? $tmdb->getTVDetail($id) : $tmdb->getMovieDetail($id);
if (!$detail) { echo '<script>showToast("获取详情失败","error");location.href="/";</script>'; }

$pageTitle = ($detail['title'] ?? $detail['name'] ?? '详情页') . ' - 在线观看';
$backdrop = $tmdb->getImageUrl($detail['backdrop_path'] ?? '', 'original');
$poster = $tmdb->getImageUrl($detail['poster_path'] ?? '', 'w500');
$title = $detail['title'] ?? $detail['name'] ?? '';
$rating = $detail['vote_average'] ?? 0;
$year = '';
if (!empty($detail['release_date'])) $year = substr($detail['release_date'], 0, 4);
if (!empty($detail['first_air_date'])) $year = substr($detail['first_air_date'], 0, 4);
$runtime = $detail['runtime'] ?? 0;
$runtimeStr = $runtime ? ($runtime < 120 ? $runtime . '分钟' : floor($runtime / 60) . '小时' . ($runtime % 60) . '分钟') : '';
$seasons = $detail['seasons'] ?? [];
$genres = [];
foreach ($detail['genres'] ?? [] as $g) $genres[] = $g['name'];
$genreStr = implode(' / ', $genres);
$overview = $detail['overview'] ?? '';
$cast = array_slice($detail['credits']['cast'] ?? [], 0, 12);
$similar = $detail['similar']['results'] ?? [];

// 判断是否外国影视作品（简单判断：原语言非zh且生产国家不含CN）
$isForeign = true;
$origLang = $detail['original_language'] ?? 'en';
if (strpos($origLang, 'zh') === 0) $isForeign = false;
$productionCountries = array_column($detail['production_countries'] ?? [], 'iso_3166_1');
if (in_array('CN', $productionCountries)) $isForeign = false;

// 取第一季详情（默认）
$seasonDetail = null;
if ($type == 'tv' && !empty($seasons)) {
    $sn = intval($_GET['season'] ?? (count($seasons) == 1 ? $seasons[0]['season_number'] : 1));
    if ($sn <= 0) $sn = 1;
    $seasonDetail = $tmdb->getSeasonDetail($id, $sn);
}

$db = Database::getInstance();
$favorited = false;
if (is_logged_in()) {
    $fav = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ? AND type = ?", [$_SESSION['user_id'], $id, $type]);
    $favorited = !empty($fav);
}
?>

<div class="detail-hero">
    <?php if ($backdrop): ?><div class="detail-backdrop" style="background-image:url('<?= $backdrop ?>')"></div><?php endif; ?>
    <div class="detail-container">
        <?php if (!$currentUser || $currentUser['status'] != 1): ?>
        <div class="need-login-tip" style="margin-bottom:30px;">
            <div class="need-login-icon"><i class="icon icon-user"></i></div>
            <div class="need-login-text">
                <h4>登录后解锁完整内容</h4>
                <p>需要登录才可以观看哦，如没有账号请注册！</p>
            </div>
            <div>
                <button class="btn btn-secondary btn-sm" data-login>登录</button>
                <button class="btn btn-primary btn-sm" data-register style="margin-left:8px;">注册</button>
            </div>
        </div>
        <?php endif; ?>

        <div class="detail-content">
            <div class="detail-poster">
                <img src="<?= $poster ?>" alt="<?= e($title) ?>" onerror="this.style.visibility='hidden'">
            </div>
            <div class="detail-info">
                <h1><?= e($title) ?></h1>
                <div class="detail-meta-row">
                    <span class="detail-rating"><i class="icon icon-star"></i> <?= number_format($rating, 1) ?> 分</span>
                    <?php if ($year): ?><span><?= $year ?></span><?php endif; ?>
                    <?php if ($type == 'tv' && !empty($detail['number_of_seasons'])): ?><span>共 <?= $detail['number_of_seasons'] ?> 季</span><?php endif; ?>
                    <?php if ($type == 'tv' && !empty($detail['number_of_episodes'])): ?><span>共 <?= $detail['number_of_episodes'] ?> 集</span><?php endif; ?>
                    <?php if ($runtimeStr): ?><span><?= $runtimeStr ?></span><?php endif; ?>
                    <span><?= $type == 'movie' ? '电影' : '剧集' ?></span>
                </div>
                <div class="detail-tags">
                    <?php foreach ($genres as $g): ?><span class="detail-tag"><?= e($g) ?></span><?php endforeach; ?>
                </div>
                <div class="detail-desc"><?= e($overview) ?></div>
                <div class="detail-actions">
                    <button class="btn btn-primary btn-lg" id="playNowBtn" onclick="onPlayNow()">
                        <i class="icon icon-play"></i> 立即播放
                    </button>
                    <button class="btn btn-secondary btn-lg" id="favBtn" onclick="toggleFavorite()" style="<?= $favorited ? 'background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.3);color:#ef4444;' : '' ?>">
                        <i class="icon <?= $favorited ? 'icon-heart' : 'icon-heart-outline' ?>"></i>
                        <span id="favText"><?= $favorited ? '已收藏' : '收藏' ?></span>
                    </button>
                </div>
                <?php if (!empty($cast)): ?>
                <div style="margin-top:24px;">
                    <div style="font-size:15px;font-weight:600;margin-bottom:12px;color:var(--text-secondary);">主要演员</div>
                    <div class="detail-cast-list">
                        <?php foreach ($cast as $c):
                            $avatar = $tmdb->getImageUrl($c['profile_path'] ?? '', 'w185');
                        ?>
                        <div class="cast-item">
                            <div class="cast-avatar">
                                <?php if ($avatar): ?><img src="<?= $avatar ?>" alt="<?= e($c['name']) ?>" loading="lazy"><?php endif; ?>
                            </div>
                            <div class="cast-name" title="<?= e($c['name']) ?>"><?= e($c['name']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if ($type == 'tv'): ?>
<!-- 季选择 -->
<?php if (!empty($seasons) && count($seasons) > 1): ?>
<div class="section">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:14px;">选择季数</h3>
    <div class="season-selector">
        <?php foreach ($seasons as $s):
            $sn = $s['season_number'];
            $active = isset($_GET['season']) ? intval($_GET['season']) == $sn : (!isset($_GET['season']) && (count($seasons) == 1 ? true : $sn == 1));
        ?>
            <a href="/detail.php?type=tv&id=<?= $id ?>&season=<?= $sn ?>" class="season-btn <?= $active ? 'active' : '' ?>">
                第 <?= $sn ?> 季<?= !empty($s['episode_count']) ? " ({$s['episode_count']}集)" : '' ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- 配音选择（仅外国影视） -->
<?php if ($isForeign): ?>
<div class="section">
    <div class="audio-selector">
        <span class="audio-label">配音版本：</span>
        <button class="audio-btn active" data-audio="original">原声</button>
        <button class="audio-btn" data-audio="mandarin">普通话配音</button>
        <span style="color:var(--text-muted);font-size:13px;margin-left:10px;">※ 切换配音后将在播放时匹配对应版本</span>
    </div>
</div>
<?php endif; ?>

<!-- 集数列表 -->
<?php if ($seasonDetail && !empty($seasonDetail['episodes'])): ?>
<div class="section">
    <div class="section-header">
        <h3 style="font-size:18px;font-weight:700;">
            选集播放
            <?php if (!empty($seasonDetail['name'])): ?><span style="font-weight:400;font-size:14px;color:var(--text-muted);margin-left:10px;"><?= e($seasonDetail['name']) ?></span><?php endif; ?>
            <?php if (!empty($seasonDetail['air_date'])): ?><span style="font-weight:400;font-size:14px;color:var(--text-muted);margin-left:10px;">· <?= substr($seasonDetail['air_date'], 0, 4) ?></span><?php endif; ?>
            <?php if (!empty($seasonDetail['vote_average'])): ?><span style="font-weight:400;font-size:14px;color:#fbbf24;margin-left:10px;">· ★ <?= number_format($seasonDetail['vote_average'], 1) ?></span><?php endif; ?>
        </h3>
    </div>
    <?php if (!empty($seasonDetail['overview'])): ?>
    <p style="color:var(--text-secondary);margin-bottom:20px;padding:14px;background:var(--bg-card);border-radius:var(--radius-md);font-size:14px;line-height:1.8;"><?= e($seasonDetail['overview']) ?></p>
    <?php endif; ?>
    <div class="episode-grid" id="episodeGrid">
        <?php foreach ($seasonDetail['episodes'] as $ep):
            $sn = $ep['season_number'] ?? 1;
            $en = $ep['episode_number'] ?? 1;
            $still = $tmdb->getImageUrl($ep['still_path'] ?? '', 'w300');
            $name = $ep['name'] ?? "第 $en 集";
            $air = !empty($ep['air_date']) ? substr($ep['air_date'], 0, 10) : '';
            $dur = !empty($ep['runtime']) ? ($ep['runtime'] . '分钟') : '';
            $playUrl = "/play.php?type=tv&id=$id&season=$sn&episode=$en";
        ?>
        <div class="episode-card" onclick="requireLogin(function(){ goToPlay(<?= $en ?>, <?= $sn ?>); })">
            <div class="episode-thumb">
                <img src="<?= $still ?>" alt="<?= e($name) ?>" loading="lazy" onerror="this.style.visibility='hidden'">
                <div class="episode-number">第 <?= $en ?> 集</div>
            </div>
            <div class="episode-info">
                <div class="episode-title" title="<?= e($name) ?>"><?= e($name) ?></div>
                <div class="episode-meta">
                    <?php if ($air) echo $air; ?>
                    <?php if ($dur) echo ' · ' . $dur; ?>
                    <?php if (!empty($ep['vote_average'])) echo ' · ★ ' . number_format($ep['vote_average'], 1); ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- 电影直接集数提示 -->
<?php if ($type == 'movie'): ?>
<div class="section">
    <div class="need-login-tip" style="margin-bottom:20px;">
        <div class="need-login-icon" style="background:#fbbf24;"><i class="icon icon-play"></i></div>
        <div class="need-login-text">
            <h4>电影播放</h4>
            <p>点击下方按钮开始播放 <?= e($title) ?></p>
        </div>
        <button class="btn btn-primary btn-lg" onclick="onPlayNow()"><i class="icon icon-play"></i> 开始播放</button>
    </div>
</div>
<?php endif; ?>

<!-- 相关推荐 -->
<?php if (!empty($similar)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">
            <span class="section-title-icon"><i class="icon icon-star"></i></span>
            相关推荐
        </h2>
    </div>
    <div class="horizontal-scroll-wrapper">
        <button class="scroll-btn prev"><i class="icon icon-chevron-left"></i></button>
        <button class="scroll-btn next"><i class="icon icon-chevron-right"></i></button>
        <div class="horizontal-scroll">
            <?php foreach (array_slice($similar, 0, 18) as $item):
                $t2 = isset($item['title']) ? 'movie' : 'tv';
                $id2 = $item['id'];
                $title2 = $item['title'] ?? $item['name'] ?? '';
                $poster2 = $tmdb->getImageUrl($item['poster_path'] ?? '', 'w500');
                $rating2 = $item['vote_average'] ?? 0;
                $year2 = '';
                if (!empty($item['release_date'])) $year2 = substr($item['release_date'], 0, 4);
                if (!empty($item['first_air_date'])) $year2 = substr($item['first_air_date'], 0, 4);
            ?>
            <div class="movie-card" onclick="requireLogin(function(){location.href='/detail.php?type=<?= $t2 ?>&id=<?= $id2 ?>';})">
                <div class="movie-poster">
                    <img src="<?= $poster2 ?>" loading="lazy" onerror="this.style.opacity=0">
                    <div class="movie-rating"><i class="icon icon-star"></i> <?= number_format($rating2, 1) ?></div>
                </div>
                <div class="movie-info">
                    <div class="movie-title"><?= e($title2) ?></div>
                    <div class="movie-subtitle"><span><?= $year2 ?></span><span>·</span><span><?= $t2 == 'movie' ? '电影' : '剧集' ?></span></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
const __DETAIL = {
    id: <?= $id ?>,
    type: '<?= $type ?>',
    title: <?= json_encode($title) ?>,
    poster: <?= json_encode($poster) ?>,
    currentSeason: <?= isset($sn) ? $sn : 1 ?>,
    currentAudio: 'original'
};

function onPlayNow() {
    requireLogin(function() {
        if (__DETAIL.type === 'movie') {
            goToPlay(1, 1);
        } else {
            goToPlay(1, __DETAIL.currentSeason);
        }
    });
}
function goToPlay(ep, season) {
    const audio = document.querySelector('.audio-btn.active');
    const audioParam = audio ? '&audio=' + audio.dataset.audio : '';
    if (__DETAIL.type === 'movie') {
        location.href = `/play.php?type=movie&id=${__DETAIL.id}${audioParam}`;
    } else {
        location.href = `/play.php?type=tv&id=${__DETAIL.id}&season=${season}&episode=${ep}${audioParam}`;
    }
}
async function toggleFavorite() {
    if (!window.__USER) { showToast('请先登录', 'warning'); openAuthModal('login'); return; }
    const res = await apiRequest('/api/user.php', { action: 'toggle_favorite', tmdb_id: __DETAIL.id, type: __DETAIL.type, title: __DETAIL.title, poster: __DETAIL.poster });
    if (res.success) {
        const btn = document.getElementById('favBtn');
        const txt = document.getElementById('favText');
        if (res.favorited) {
            btn.style.cssText = 'background:rgba(239,68,68,0.15);border-color:rgba(239,68,68,0.3);color:#ef4444;';
            btn.querySelector('.icon').className = 'icon icon-heart';
            txt.textContent = '已收藏';
        } else {
            btn.style.cssText = '';
            btn.querySelector('.icon').className = 'icon icon-heart-outline';
            txt.textContent = '收藏';
        }
        showToast(res.message, 'success');
    }
}
document.querySelectorAll('.audio-btn').forEach(b => {
    b.addEventListener('click', function() {
        document.querySelectorAll('.audio-btn').forEach(x => x.classList.remove('active'));
        this.classList.add('active');
        __DETAIL.currentAudio = this.dataset.audio;
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
