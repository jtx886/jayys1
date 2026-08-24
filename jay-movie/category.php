<?php
$type = $_GET['type'] ?? 'movie';
$genre = intval($_GET['genre'] ?? 0);
$navActive = $type;
$pageTitle = ($type == 'movie' ? '电影' : '电视剧') . ' - 在线观看';
$bodyPage = 'category';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/tmdb.php';

$tmdb = new TMDB();
$genres = $tmdb->getGenres($type) ?: ['genres' => []];
$page = max(1, intval($_GET['page'] ?? 1));
$params = ['page' => $page];
if ($genre > 0) $params['with_genres'] = $genre;
$list = $tmdb->discover($type, $params);
$totalPages = $list['total_pages'] ?? 1;
if ($totalPages > 500) $totalPages = 500;

$genreName = '';
foreach ($genres['genres'] ?? [] as $g) if ($g['id'] == $genre) $genreName = $g['name'];
if (!$genreName) $genreName = '全部';
?>

<div class="section">
    <div style="background:linear-gradient(135deg,var(--theme-color)20,var(--theme-dark)10);border-radius:var(--radius-xl);padding:36px;border:1px solid var(--theme-color)30;margin-bottom:24px;">
        <h1 style="font-size:32px;font-weight:800;margin-bottom:8px;"><?= $type == 'movie' ? '🎬 电影大全' : '📺 电视剧集' ?></h1>
        <p style="color:var(--text-secondary);font-size:15px;">正在浏览：<span style="color:var(--theme-color);font-weight:600;"><?= e($genreName) ?></span> · 共约 <?= number_format($list['total_results'] ?? 0) ?> 部作品</p>
    </div>

    <div class="category-tabs">
        <a href="/category.php?type=<?= $type ?>" class="category-tab <?= $genre == 0 ? 'active' : '' ?>">全部</a>
        <?php foreach ($genres['genres'] ?? [] as $g): ?>
            <a href="/category.php?type=<?= $type ?>&genre=<?= $g['id'] ?>" class="category-tab <?= $g['id'] == $genre ? 'active' : '' ?>"><?= e($g['name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="movie-grid">
        <?php
        $results = $list['results'] ?? [];
        foreach ($results as $m) {
            $title = $m['title'] ?? $m['name'] ?? '';
            $poster = $tmdb->getImageUrl($m['poster_path'] ?? '', 'w500');
            $rating = $m['vote_average'] ?? 0;
            $y = '';
            if (!empty($m['release_date'])) $y = substr($m['release_date'], 0, 4);
            if (!empty($m['first_air_date'])) $y = substr($m['first_air_date'], 0, 4);
            $typeText = $type == 'movie' ? '电影' : '剧集';
            $url = "/detail.php?type=$type&id={$m['id']}";
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
            echo '<div style="grid-column:1/-1;padding:80px;text-align:center;color:var(--text-muted);">暂无内容</div>';
        }
        ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a class="page-btn" href="/category.php?type=<?= $type ?>&genre=<?= $genre ?>&page=<?= $page - 1 ?>"><i class="icon icon-chevron-left"></i></a>
        <?php else: ?>
            <button class="page-btn" disabled><i class="icon icon-chevron-left"></i></button>
        <?php endif;
        $start = max(1, $page - 4);
        $end = min($totalPages, $start + 8);
        if ($end - $start < 8) $start = max(1, $end - 8);
        for ($i = $start; $i <= $end; $i++): ?>
            <a class="page-btn <?= $i == $page ? 'active' : '' ?>" href="/category.php?type=<?= $type ?>&genre=<?= $genre ?>&page=<?= $i ?>"><?= $i ?></a>
        <?php endfor;
        if ($page < $totalPages): ?>
            <a class="page-btn" href="/category.php?type=<?= $type ?>&genre=<?= $genre ?>&page=<?= $page + 1 ?>"><i class="icon icon-chevron-right"></i></a>
        <?php else: ?>
            <button class="page-btn" disabled><i class="icon icon-chevron-right"></i></button>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
