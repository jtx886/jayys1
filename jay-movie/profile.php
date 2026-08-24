<?php
$navActive = 'profile';
$pageTitle = '个人中心 - Jay影视';
$bodyPage = 'profile';
require_once __DIR__ . '/includes/header.php';

// 未登录
if (!$currentUser) {
    echo '<div class="main-container"><div class="need-login-tip" style="margin-top:30px;">
        <div class="need-login-icon"><i class="icon icon-user"></i></div>
        <div class="need-login-text">
            <h4>需要登录才可以访问个人中心</h4>
            <p>如没有账号请注册！</p>
        </div>
        <div>
            <button class="btn btn-secondary" data-login>登录</button>
            <button class="btn btn-primary" data-register style="margin-left:8px;">注册</button>
        </div>
    </div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
if ($currentUser['status'] != 1) {
    echo '<div class="main-container"><div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:var(--radius-lg);padding:30px;text-align:center;margin-top:30px;">
        <h3 style="color:#ef4444;margin-bottom:12px;">🔒 账号已被封禁</h3>';
    if (!empty($currentUser['ban_reason'])) echo '<p style="color:var(--text-secondary);margin-bottom:8px;">封禁原因：'.e($currentUser['ban_reason']).'</p>';
    if (!empty($currentUser['unban_time'])) echo '<p style="color:var(--text-secondary);">解封时间：'.date('Y-m-d H:i:s', strtotime($currentUser['unban_time'])).'</p>';
    else echo '<p style="color:var(--text-secondary);">永久封禁，如有疑问请联系管理员</p>';
    echo '</div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$tab = $_GET['tab'] ?? 'profile';
$db = Database::getInstance();

// 统计
$stat = [
    'favorites' => $db->fetchOne("SELECT COUNT(*) c FROM favorites WHERE user_id = ?", [$currentUser['id']])['c'],
    'history' => $db->fetchOne("SELECT COUNT(*) c FROM watch_history WHERE user_id = ?", [$currentUser['id']])['c'],
    'watch_seconds' => $db->fetchOne("SELECT COALESCE(SUM(watch_seconds),0) c FROM watch_history WHERE user_id = ?", [$currentUser['id']])['c']
];
?>

<div class="profile-header">
    <div class="profile-info">
        <div class="profile-avatar" onclick="openAvatarModal()">
            <?php if (!empty($currentUser['avatar']) && strpos($currentUser['avatar'], 'data:') === 0 || !empty($currentUser['avatar']) && strpos($currentUser['avatar'], 'http') === 0): ?>
                <img src="<?= e($currentUser['avatar']) ?>" alt="头像">
            <?php else: ?>
                <?= mb_substr($currentUser['username'], 0, 1, 'UTF-8') ?>
            <?php endif; ?>
            <div class="profile-avatar-edit"><i class="icon icon-camera"></i> 更换</div>
        </div>
        <div class="profile-text" style="flex:1;">
            <h2>
                <?= e($currentUser['username']) ?>
                <?php if ($currentUser['is_admin']): ?>
                    <span class="admin-badge"><span class="admin-badge-icon"></span>开发者</span>
                <?php endif; ?>
            </h2>
            <div class="profile-email">📧 <?= e($currentUser['email']) ?> · 注册于 <?= date('Y-m-d', strtotime($currentUser['created_at'])) ?></div>
        </div>
        <div style="display:flex;gap:24px;">
            <div style="text-align:center;">
                <div style="font-size:24px;font-weight:800;color:var(--theme-color);"><?= $stat['favorites'] ?></div>
                <div style="color:var(--text-secondary);font-size:12px;">收藏</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:24px;font-weight:800;color:#fbbf24;"><?= $stat['history'] ?></div>
                <div style="color:var(--text-secondary);font-size:12px;">看过</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:24px;font-weight:800;color:#10b981;"><?= ceil($stat['watch_seconds']/60) ?>分钟</div>
                <div style="color:var(--text-secondary);font-size:12px;">观看时长</div>
            </div>
        </div>
    </div>
</div>

<div class="profile-tabs">
    <div class="profile-tab <?= $tab == 'profile' ? 'active' : '' ?>" onclick="switchTab('profile')"><i class="icon icon-user"></i> 我的资料</div>
    <div class="profile-tab <?= $tab == 'favorites' ? 'active' : '' ?>" onclick="switchTab('favorites')"><i class="icon icon-heart-outline"></i> 我的收藏 <span style="margin-left:6px;opacity:0.7;">(<?= $stat['favorites'] ?>)</span></div>
    <div class="profile-tab <?= $tab == 'history' ? 'active' : '' ?>" onclick="switchTab('history')"><i class="icon icon-clock"></i> 观看历史 <span style="margin-left:6px;opacity:0.7;">(<?= $stat['history'] ?>)</span></div>
    <?php if ($currentUser['is_admin']): ?>
    <div class="profile-tab" onclick="location.href='/admin/'"><i class="icon icon-dashboard"></i> 管理后台</div>
    <?php endif; ?>
</div>

<div id="tabContent">
    <!-- 资料 -->
    <?php if ($tab == 'profile'): ?>
    <div class="admin-panel">
        <div class="admin-panel-header"><h3 class="admin-panel-title">基本资料</h3></div>
        <div class="admin-panel-body">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px;">
                <div>
                    <label class="form-label">用户ID</label>
                    <div class="form-input" style="display:flex;align-items:center;color:var(--text-secondary);"><?= $currentUser['id'] ?></div>
                </div>
                <div>
                    <label class="form-label">用户名</label>
                    <div class="form-input" style="display:flex;align-items:center;"><?= e($currentUser['username']) ?></div>
                </div>
                <div>
                    <label class="form-label">邮箱</label>
                    <div class="form-input" style="display:flex;align-items:center;"><?= e($currentUser['email']) ?></div>
                </div>
                <div>
                    <label class="form-label">账号类型</label>
                    <div class="form-input" style="display:flex;align-items:center;gap:8px;">
                        <?= $currentUser['is_admin'] ? '<span style="color:#ef4444;font-weight:600;">管理员</span><span class="admin-badge"><span class="admin-badge-icon"></span>开发者</span>' : '<span style="color:var(--text-secondary);">普通用户</span>' ?>
                    </div>
                </div>
                <div>
                    <label class="form-label">注册时间</label>
                    <div class="form-input" style="display:flex;align-items:center;color:var(--text-secondary);"><?= $currentUser['created_at'] ?></div>
                </div>
                <div>
                    <label class="form-label">上次登录</label>
                    <div class="form-input" style="display:flex;align-items:center;color:var(--text-secondary);"><?= $currentUser['last_login'] ?? '首次登录' ?></div>
                </div>
            </div>
            <div style="margin-top:24px;">
                <button class="btn btn-secondary" onclick="openAvatarModal()"><i class="icon icon-camera"></i> 修改头像</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 收藏 -->
    <?php if ($tab == 'favorites'): ?>
    <div id="favoritesList">
        <div class="admin-panel" style="border:none;background:none;padding:0;box-shadow:none;">
            <div class="admin-panel-header" style="padding:0 0 16px;border:none;background:none;">
                <h3 class="admin-panel-title">我的收藏 (<?= $stat['favorites'] ?>)</h3>
                <div></div>
            </div>
            <div id="favoritesGrid" class="movie-grid">
                <div style="grid-column:1/-1;padding:40px;text-align:center;"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>
    <script>loadFavorites();</script>
    <?php endif; ?>

    <!-- 观看历史 -->
    <?php if ($tab == 'history'): ?>
    <div id="historyList">
        <div class="admin-panel" style="border:none;background:none;padding:0;">
            <div class="admin-panel-header" style="padding:0 0 16px;border:none;background:none;">
                <h3 class="admin-panel-title">观看历史 (<?= $stat['history'] ?>)</h3>
                <button class="btn btn-danger btn-sm" onclick="clearAllHistory()"><i class="icon icon-trash"></i> 清空全部</button>
            </div>
            <div id="historyGrid" class="movie-grid">
                <div style="grid-column:1/-1;padding:40px;text-align:center;"><div class="loading-spinner"></div></div>
            </div>
        </div>
    </div>
    <script>loadHistory();</script>
    <?php endif; ?>
</div>

<script>
function switchTab(tab) {
    const url = new URL(location.href);
    url.searchParams.set('tab', tab);
    location.href = url.toString();
}

function loadFavorites() {
    fetch('/api/user.php?action=get_favorites').then(r => r.json()).then(data => {
        const grid = document.getElementById('favoritesGrid');
        if (!data.success || !data.list.length) {
            grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="icon icon-heart-outline"></i><h3>还没有收藏</h3><p>快去首页收藏喜欢的影视吧</p></div>';
            return;
        }
        grid.innerHTML = data.list.map(item => {
            const url = `/detail.php?type=${item.type}&id=${item.tmdb_id}`;
            return `<div class="movie-card" onclick="requireLogin(function(){location.href='${url}';})">
                <div class="movie-poster" style="position:relative;">
                    <img src="${escapeHtml(item.poster)}" loading="lazy" onerror="this.style.opacity=0">
                    <button class="history-delete-btn" style="position:absolute;top:8px;right:8px;z-index:5;" onclick="event.stopPropagation();removeFav(${item.id})">删除</button>
                </div>
                <div class="movie-info">
                    <div class="movie-title">${escapeHtml(item.title)}</div>
                    <div class="movie-subtitle"><span>${item.created_at.substring(0,10)}</span><span>·</span><span>${item.type==='movie'?'电影':'剧集'}</span></div>
                </div>
            </div>`;
        }).join('');
    });
}
async function removeFav(id) {
    if (!confirm('确认取消收藏？')) return;
    const r = await apiRequest('/api/user.php', { action: 'remove_favorite', id });
    if (r.success) { showToast('已取消收藏', 'success'); loadFavorites(); }
}

function loadHistory() {
    fetch('/api/user.php?action=get_watch_history').then(r => r.json()).then(data => {
        const grid = document.getElementById('historyGrid');
        if (!data.success || !data.list.length) {
            grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1;"><i class="icon icon-clock"></i><h3>还没有观看记录</h3><p>快去看电影吧~</p></div>';
            return;
        }
        grid.innerHTML = data.list.map(item => {
            const url = `/detail.php?type=${item.type}&id=${item.tmdb_id}`;
            const mins = Math.floor(item.watch_seconds / 60);
            const tag = item.season_number ? `S${item.season_number}E${item.episode_number}` : '';
            return `<div class="movie-card" onclick="requireLogin(function(){location.href='${url}';})">
                <div class="movie-poster" style="position:relative;">
                    <img src="${escapeHtml(item.poster)}" loading="lazy" onerror="this.style.opacity=0">
                    <button class="history-delete-btn" style="position:absolute;top:8px;right:8px;z-index:5;" onclick="event.stopPropagation();removeHistory(${item.id})">删除</button>
                    ${mins ? `<div style="position:absolute;bottom:8px;left:8px;z-index:5;padding:3px 8px;background:rgba(0,0,0,0.75);border-radius:4px;font-size:11px;color:#fff;">已看 ${mins}分钟</div>` : ''}
                </div>
                <div class="movie-info">
                    <div class="movie-title">${escapeHtml(item.title)} ${tag ? `<span style="color:var(--text-muted);font-size:11px;">${tag}</span>` : ''}</div>
                    <div class="movie-subtitle"><span>${item.updated_at.substring(0,10)}</span></div>
                </div>
            </div>`;
        }).join('');
    });
}
async function removeHistory(id) {
    if (!confirm('确认删除此观看记录？')) return;
    const r = await apiRequest('/api/user.php', { action: 'remove_watch_history', id });
    if (r.success) { showToast('已删除', 'success'); loadHistory(); }
}
async function clearAllHistory() {
    if (!confirm('确认清空所有观看历史？该操作不可恢复。')) return;
    const r = await apiRequest('/api/user.php', { action: 'clear_watch_history' });
    if (r.success) { showToast('已清空全部历史', 'success'); loadHistory(); }
}

// 头像弹窗
function openAvatarModal() {
    let m = document.getElementById('avatarModal');
    if (!m) {
        m = document.createElement('div');
        m.id = 'avatarModal';
        m.className = 'modal-overlay';
        const colors = ['#6366f1','#ec4899','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#0ea5e9'];
        const letters = Array.from(new Set([...'ABCDEFGHIJKLMNOPQRSTUVWXYZ', ...'0123456789', ...'杰影酷帅666牛']))
            .slice(0, 12);
        const letterChar = (<?= json_encode(mb_substr($currentUser['username'],0,1,'UTF-8')) ?>) || 'J';
        const options = [];
        colors.forEach((c, i) => {
            const ch = i < letters.length ? letters[i] : letterChar;
            options.push(`<div class="avatar-option" data-avatar="data:image/svg+xml;utf8,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' width='100' height='100'><rect width='100' height='100' rx='50' fill='${c}'/><text x='50' y='50' text-anchor='middle' dominant-baseline='middle' fill='white' font-size='42' font-weight='bold' font-family='sans-serif'>${ch}</text></svg>`)}" style="background:${c};">${ch}</div>`);
        });
        m.innerHTML = `<div class="modal">
            <div class="modal-header">
                <div><div class="modal-title">选择头像</div><div class="modal-subtitle">选择一个您喜欢的头像，或使用自定义图片</div></div>
                <button class="modal-close" onclick="document.getElementById('avatarModal').classList.remove('show')"><i class="icon icon-x"></i></button>
            </div>
            <div class="modal-body avatar-upload-modal">
                <div style="margin-bottom:14px;"><label class="form-label">选择预设头像</label></div>
                <div class="avatar-options">${options.join('')}</div>
                <div style="margin:20px 0 10px;"><label class="form-label">或上传自定义头像</label></div>
                <input type="file" id="avatarFile" accept="image/*" class="form-input" style="height:auto;padding:10px;">
                <div style="margin-top:16px;">
                    <button class="btn btn-primary form-submit-btn" onclick="saveAvatar()" style="width:auto;">保存头像</button>
                </div>
            </div>
        </div>`;
        document.body.appendChild(m);
        let selectedAvatar = null;
        m.querySelectorAll('.avatar-option').forEach(opt => {
            opt.addEventListener('click', () => {
                m.querySelectorAll('.avatar-option').forEach(o => o.classList.remove('selected'));
                opt.classList.add('selected');
                selectedAvatar = opt.dataset.avatar;
            });
        });
        document.getElementById('avatarFile').addEventListener('change', function() {
            const f = this.files[0];
            if (!f) return;
            if (f.size > 2 * 1024 * 1024) { showToast('图片不能超过2MB', 'error'); return; }
            const r = new FileReader();
            r.onload = e => {
                selectedAvatar = e.target.result;
                m.querySelectorAll('.avatar-option').forEach(o => o.classList.remove('selected'));
            };
            r.readAsDataURL(f);
        });
        m.saveAvatarFunc = async function() {
            if (!selectedAvatar) { showToast('请选择或上传头像', 'warning'); return; }
            const r = await apiRequest('/api/user.php', { action: 'update_avatar', avatar: selectedAvatar });
            if (r.success) {
                showToast('头像更新成功', 'success');
                m.classList.remove('show');
                setTimeout(() => location.reload(), 600);
            } else showToast(r.message || '保存失败', 'error');
        };
    }
    m.classList.add('show');
}
function saveAvatar() { const m = document.getElementById('avatarModal'); if (m && m.saveAvatarFunc) m.saveAvatarFunc(); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
