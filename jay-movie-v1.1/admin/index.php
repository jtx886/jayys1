<?php
require_once dirname(__FILE__) . '/../includes/functions.php';

// 权限检查
if (!$currentUser || $currentUser['is_admin'] != 1) {
    // 不是管理员，跳转到登录
    header('Location: /');
    exit;
}

// 检查数据库连接
$db = Database::getInstance();
if (!$db->getConnection()) {
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Jay影视管理后台 - 安装</title>';
    echo '<link rel="stylesheet" href="/assets/css/style.css"></head><body><div class="main-container">';
    echo '<div style="max-width:700px;margin:60px auto;background:var(--bg-card);border:1px solid var(--border-color);border-radius:var(--radius-xl);padding:40px;">';
    echo '<h1 style="font-size:28px;font-weight:800;margin-bottom:20px;display:flex;align-items:center;gap:10px;">';
    echo '<span style="display:inline-block;width:42px;height:42px;background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));border-radius:10px;position:relative;">';
    echo '<span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;">J</span></span> Jay影视 安装向导</h1>';
    echo '<div class="need-login-tip" style="margin-bottom:20px;">';
    echo '<div class="need-login-icon" style="background:#fbbf24;"><i class="icon icon-settings"></i></div>';
    echo '<div class="need-login-text"><h4>需要先配置数据库</h4><p>请先修改 /config.php 中的数据库配置，然后执行 install/database.sql 进行数据库安装。</p></div></div>';
    echo '<p style="color:var(--text-secondary);margin-bottom:16px;line-height:1.8;">默认管理员账号：<b style="color:#fff;">杰同学</b>，密码：<b style="color:#fff;">101113</b></p>';
    echo '<pre style="background:#000;padding:16px;border-radius:var(--radius-md);overflow:auto;font-size:12px;">';
    echo htmlspecialchars(file_get_contents(dirname(__FILE__).'/../install/database.sql'));
    echo '</pre>';
    echo '</div></div></body></html>';
    exit;
}

$pageTitle = '管理后台 - Jay影视';
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="/assets/css/style.css?v=20240101">
<script>window.__THEME_COLOR = '<?= addslashes(defined('THEME_COLOR') ? THEME_COLOR : '#6366f1') ?>';</script>
<style>
.admin-sidebar-overlay.show { display: block; }
</style>
</head>
<body>

<div class="admin-layout">
    <div class="admin-sidebar-overlay" id="sideOverlay" onclick="document.getElementById('adminSidebar').classList.remove('show');this.classList.remove('show');"></div>
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-logo">
            <a href="/" style="display:inline-flex;align-items:center;gap:10px;font-weight:800;font-size:20px;color:#fff;">
                <span style="display:inline-block;width:36px;height:36px;background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));border-radius:10px;position:relative;">
                    <span style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;">J</span>
                </span>
                <span>Jay影视</span>
            </a>
            <div style="font-size:12px;color:var(--text-muted);margin-top:6px;display:flex;align-items:center;gap:6px;">
                <span class="admin-badge" style="margin:0;padding:2px 8px;font-size:10px;"><span class="admin-badge-icon" style="width:9px;height:9px;margin-right:3px;"></span>开发者后台</span>
            </div>
        </div>
        <nav class="admin-menu">
            <div class="admin-menu-item active" data-menu="dashboard" onclick="switchMenu('dashboard')"><i class="icon icon-dashboard"></i>仪表盘</div>
            <div class="admin-menu-item" data-menu="users" onclick="switchMenu('users')"><i class="icon icon-user"></i>用户管理</div>
            <div class="admin-menu-item" data-menu="history" onclick="switchMenu('history')"><i class="icon icon-clock"></i>观看历史</div>
            <div class="admin-menu-item" data-menu="favorites" onclick="switchMenu('favorites')"><i class="icon icon-heart-outline"></i>用户收藏</div>
            <div class="admin-menu-item" data-menu="sources" onclick="switchMenu('sources')"><i class="icon icon-play"></i>播放源管理</div>
            <div class="admin-menu-item" data-menu="announcements" onclick="switchMenu('announcements')"><i class="icon icon-bell"></i>公告管理</div>
            <div class="admin-menu-item" data-menu="feedbacks" onclick="switchMenu('feedbacks')"><i class="icon icon-feedback"></i>反馈管理</div>
            <div class="admin-menu-item" data-menu="settings" onclick="switchMenu('settings')"><i class="icon icon-settings"></i>网站设置</div>
            <div style="height:16px;"></div>
            <div class="admin-menu-item" onclick="location.href='/'"><i class="icon icon-home"></i>返回前台</div>
            <div class="admin-menu-item" onclick="doLogout()"><i class="icon icon-x"></i>退出登录</div>
        </nav>
    </aside>

    <div class="admin-content">
        <div class="admin-header">
            <div style="display:flex;align-items:center;gap:14px;">
                <button class="admin-mobile-toggle" onclick="const s=document.getElementById('adminSidebar');const o=document.getElementById('sideOverlay');s.classList.toggle('show');o.classList.toggle('show');"><i class="icon icon-menu"></i></button>
                <h1 id="pageTitle">仪表盘</h1>
            </div>
            <div class="admin-user-info">
                <div class="admin-avatar-name">
                    <div class="user-avatar" style="width:36px;height:36px;font-size:14px;">
                        <?php if (!empty($currentUser['avatar'])): ?><img src="<?= e($currentUser['avatar']) ?>"><?php else: ?><?= mb_substr($currentUser['username'],0,1,'UTF-8') ?><?php endif; ?>
                    </div>
                    <span style="font-weight:600;display:flex;align-items:center;gap:8px;">
                        <?= e($currentUser['username']) ?>
                        <span class="admin-badge" style="padding:2px 8px;font-size:11px;"><span class="admin-badge-icon" style="width:9px;height:9px;margin-right:3px;"></span>开发者</span>
                    </span>
                </div>
            </div>
        </div>

        <!-- 仪表盘 -->
        <div id="panel-dashboard" class="admin-panel-wrapper"></div>
        <div id="panel-users" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-history" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-favorites" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-sources" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-announcements" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-feedbacks" class="admin-panel-wrapper" style="display:none;"></div>
        <div id="panel-settings" class="admin-panel-wrapper" style="display:none;"></div>
    </div>
</div>

<script src="/assets/js/main.js?v=20240101"></script>
<script>
const API = '/admin/api.php';
let currentMenu = 'dashboard';

function switchMenu(menu) {
    currentMenu = menu;
    document.querySelectorAll('.admin-menu-item[data-menu]').forEach(i => i.classList.toggle('active', i.dataset.menu === menu));
    document.querySelectorAll('.admin-panel-wrapper').forEach(p => p.style.display = 'none');
    const titles = {dashboard:'仪表盘',users:'用户管理',history:'观看历史模块',favorites:'用户收藏模块',sources:'播放源管理',announcements:'公告管理',feedbacks:'反馈管理',settings:'网站设置'};
    document.getElementById('pageTitle').textContent = titles[menu] || '管理后台';
    document.getElementById('adminSidebar').classList.remove('show');
    document.getElementById('sideOverlay').classList.remove('show');
    const renderers = {dashboard: renderDashboard, users: renderUsers, history: renderHistory, favorites: renderFavorites, sources: renderSources, announcements: renderAnnouncements, feedbacks: renderFeedbacks, settings: renderSettings};
    if (renderers[menu]) renderers[menu]();
}

async function doLogout() {
    await fetch('/api/auth.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=logout'});
    location.href = '/';
}

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s === null || s === undefined ? '' : String(s);
    return d.innerHTML;
}

// ========== 仪表盘 ==========
async function renderDashboard() {
    const wrap = document.getElementById('panel-dashboard');
    wrap.innerHTML = '<div style="padding:40px;text-align:center;"><div class="loading-spinner" style="width:32px;height:32px;border-width:3px;"></div></div>';
    try {
        const r = await (await fetch(API + '?action=dashboard')).json();
        if (!r.success) { wrap.innerHTML = '<div class="admin-panel"><div class="admin-panel-body" style="color:#ef4444;">加载失败：' + r.message + '</div></div>'; return; }
        const s = r.stats;
        wrap.innerHTML = `
        <div class="stat-grid">
            <div class="stat-card"><div class="stat-label">用户总数</div><div class="stat-value">${s.total_users}</div><div class="stat-trend">+${s.today_register} 今日新增</div><div class="stat-icon" style="color:#6366f1;background:#6366f130;"><i class="icon icon-user"></i></div></div>
            <div class="stat-card"><div class="stat-label">待处理反馈</div><div class="stat-value" style="color:#f59e0b;">${s.pending_feedbacks}</div><div class="stat-trend" style="color:#f59e0b;">总反馈 ${s.total_feedbacks}</div><div class="stat-icon" style="color:#f59e0b;background:#f59e0b30;"><i class="icon icon-feedback"></i></div></div>
            <div class="stat-card"><div class="stat-label">观看记录</div><div class="stat-value" style="color:#10b981;">${s.total_watch}</div><div class="stat-trend" style="color:#10b981;">累计播放</div><div class="stat-icon" style="color:#10b981;background:#10b98130;"><i class="icon icon-play"></i></div></div>
            <div class="stat-card"><div class="stat-label">用户收藏</div><div class="stat-value" style="color:#ec4899;">${s.total_favorites}</div><div class="stat-trend" style="color:#ef4444;">封禁账号 ${s.banned_users}</div><div class="stat-icon" style="color:#ec4899;background:#ec489930;"><i class="icon icon-heart"></i></div></div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
            <div class="admin-panel">
                <div class="admin-panel-header"><h3 class="admin-panel-title">最新注册用户</h3><button class="btn btn-secondary btn-sm" onclick="switchMenu('users')">查看全部</button></div>
                <div class="admin-panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead><tr><th>用户</th><th>邮箱</th><th>状态</th><th>注册时间</th></tr></thead>
                        <tbody>
                            ${r.latest_users.map(u => `
                            <tr>
                                <td style="font-weight:600;">${escapeHtml(u.username)} ${u.is_admin?'<span class="admin-badge" style="padding:2px 8px;font-size:10px;margin-left:4px;"><span class="admin-badge-icon" style="width:8px;height:8px;margin-right:2px;"></span>开发者</span>':''}</td>
                                <td style="color:var(--text-muted);">${escapeHtml(u.email)}</td>
                                <td>${u.status == 1 ? '<span><span class="status-dot active"></span>正常</span>' : '<span><span class="status-dot banned"></span>已封禁</span>'}</td>
                                <td style="color:var(--text-muted);">${(u.created_at||'').substring(0,16)}</td>
                            </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="admin-panel">
                <div class="admin-panel-header"><h3 class="admin-panel-title">最新反馈</h3><button class="btn btn-secondary btn-sm" onclick="switchMenu('feedbacks')">查看全部</button></div>
                <div class="admin-panel-body" style="padding:0;">
                    <table class="data-table">
                        <thead><tr><th>用户</th><th>标题</th><th>状态</th><th>时间</th></tr></thead>
                        <tbody>
                            ${r.latest_feedbacks.map(f => {
                                const scls = 'status-'+(f.status||'pending');
                                const stxt = {pending:'待处理',replied:'已回复',resolved:'已解决',closed:'已关闭'}[f.status||'pending']||'待处理';
                                return `<tr>
                                    <td style="font-weight:500;">${escapeHtml(f.username||'')}</td>
                                    <td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(f.title)}</td>
                                    <td><span class="status-badge ${scls}">${stxt}</span></td>
                                    <td style="color:var(--text-muted);">${(f.created_at||'').substring(0,16)}</td>
                                </tr>`;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        `;
    } catch(e) { wrap.innerHTML = '<div class="admin-panel"><div class="admin-panel-body" style="color:#ef4444;">加载失败</div></div>'; }
}

// ========== 用户管理 ==========
let usersPage = 1, usersKw = '';
async function renderUsers() {
    const wrap = document.getElementById('panel-users');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title">用户列表</h3>
            <div style="display:flex;gap:10px;">
                <input class="form-input" id="userKw" placeholder="搜索用户名或邮箱" style="width:240px;height:40px;" value="${usersKw}">
                <button class="btn btn-primary btn-sm" onclick="usersKw=document.getElementById('userKw').value.trim();usersPage=1;renderUsers();">搜索</button>
            </div>
        </div>
        <div class="admin-panel-body" id="usersBody">
            <div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div>
        </div>
    </div>`;
    const r = await (await fetch(API + `?action=user_list&page=${usersPage}&keyword=${encodeURIComponent(usersKw)}`)).json();
    const body = document.getElementById('usersBody');
    if (!r.success) { body.innerHTML = '<div style="color:#ef4444;">加载失败</div>'; return; }
    const pages = Math.ceil(r.total / r.per_page);
    body.innerHTML = `
    <table class="data-table" style="margin:-20px -24px;width:calc(100% + 48px);">
        <thead><tr><th>ID</th><th>用户名</th><th>邮箱</th><th>状态</th><th>封禁到期</th><th>注册时间</th><th>操作</th></tr></thead>
        <tbody>
            ${r.list.map(u => {
                const adminTag = u.is_admin ? '<span class="admin-badge" style="padding:2px 8px;font-size:10px;margin-left:4px;"><span class="admin-badge-icon" style="width:8px;height:8px;margin-right:2px;"></span>开发者</span>' : '';
                const unban = u.status == 1 ? '正常' : (u.unban_time ? u.unban_time.substring(0,16) : '永久封禁');
                return `<tr>
                    <td>${u.id}</td>
                    <td style="font-weight:600;">${escapeHtml(u.username)} ${adminTag}</td>
                    <td style="color:var(--text-muted);">${escapeHtml(u.email)}</td>
                    <td>${u.status == 1 ? '<span><span class="status-dot active"></span>正常</span>' : '<span><span class="status-dot banned"></span>已封禁</span>'}</td>
                    <td style="color:var(--text-muted);font-size:12px;">${u.status == 0 ? escapeHtml(unban) : '-'}</td>
                    <td style="color:var(--text-muted);font-size:12px;">${(u.created_at||'').substring(0,16)}</td>
                    <td>
                        <div class="action-btns">
                            <button class="action-btn view" onclick="viewUserHistory(${u.id})">历史</button>
                            <button class="action-btn view" onclick="viewUserFavorites(${u.id})">收藏</button>
                            <button class="action-btn edit" onclick="sendUserEmail(${u.id}, '${escapeHtml(u.username)}', '${escapeHtml(u.email)}')">邮件</button>
                            ${u.status == 1 && !u.is_admin ? `<button class="action-btn delete" onclick="banUser(${u.id}, '${escapeHtml(u.username)}')">封禁</button>` : ''}
                            ${u.status == 0 ? `<button class="action-btn edit" onclick="unbanUser(${u.id})">解封</button>` : ''}
                        </div>
                    </td>
                </tr>`;
            }).join('')}
        </tbody>
    </table>
    ${pages > 1 ? `<div class="pagination">
        <button class="page-btn" ${usersPage<=1?'disabled':''} onclick="if(usersPage>1){usersPage--;renderUsers();}"><i class="icon icon-chevron-left"></i></button>
        ${Array.from({length: Math.min(10, pages)}, (_, i) => {
            const p = usersPage - 5 + i;
            const start = Math.max(1, Math.min(pages - 9, usersPage - 4));
            const pp = start + i;
            if (pp > pages) return '';
            return `<button class="page-btn ${pp === usersPage ? 'active' : ''}" onclick="usersPage=${pp};renderUsers();">${pp}</button>`;
        }).join('')}
        <button class="page-btn" ${usersPage>=pages?'disabled':''} onclick="if(usersPage<${pages}){usersPage++;renderUsers();}"><i class="icon icon-chevron-right"></i></button>
    </div>` : ''}`;
}

async function banUser(uid, name) {
    const html = `<div>
        <div class="form-group"><label class="form-label">封禁用户：${escapeHtml(name)} (ID: ${uid})</label>
            <select class="form-select" id="banDays">
                <option value="1">1 天</option><option value="3">3 天</option><option value="7">7 天</option>
                <option value="30">30 天</option><option value="0">永久封禁</option>
            </select>
        </div>
        <div class="form-group"><label class="form-label">封禁原因</label>
            <input type="text" id="banReason" class="form-input" value="违反社区规定">
        </div>
    </div>`;
    const btn = await showConfirm('封禁用户', html, '确认封禁并发送通知邮件');
    if (btn) {
        const days = parseInt(document.getElementById('banDays').value || 0, 10);
        const reason = document.getElementById('banReason').value.trim();
        const r = await apiRequest(API, { action: 'ban_user', user_id: uid, days, reason });
        if (r.success) { showToast(r.message, 'success'); renderUsers(); } else showToast(r.message, 'error');
    }
}
async function unbanUser(uid) {
    if (!confirm('确认解封该用户？')) return;
    const r = await apiRequest(API, { action: 'unban_user', user_id: uid });
    if (r.success) { showToast(r.message, 'success'); renderUsers(); }
}
async function sendUserEmail(uid, username, email) {
    const html = `<div>
        <div class="form-group"><label class="form-label">收件人：${escapeHtml(username)} (${escapeHtml(email)})</label></div>
        <div class="form-group"><label class="form-label">邮件标题</label><input type="text" id="emailTitle" class="form-input" placeholder="请输入邮件标题"></div>
        <div class="form-group"><label class="form-label">邮件内容</label><textarea id="emailContent" class="form-textarea" rows="6" placeholder="请输入邮件内容..."></textarea></div>
    </div>`;
    const btn = await showConfirm('发送自定义邮件通知', html, '发送邮件');
    if (btn) {
        const title = document.getElementById('emailTitle').value.trim();
        const content = document.getElementById('emailContent').value.trim();
        if (!title || !content) { showToast('标题和内容不能空', 'warning'); return; }
        const r = await apiRequest(API, { action: 'send_email_to_user', user_id: uid, title, content });
        showToast(r.message, r.success ? 'success' : 'error');
    }
}
async function viewUserHistory(uid) {
    const r = await (await fetch(API + `?action=user_history&user_id=${uid}`)).json();
    const html = r.success && r.list.length
        ? `<div style="max-height:500px;overflow:auto;"><table class="data-table"><thead><tr><th>影视</th><th>集数</th><th>观看时长</th><th>最近观看</th></tr></thead><tbody>
            ${r.list.map(h => `<tr>
                <td style="font-weight:500;">${escapeHtml(h.title)}</td>
                <td style="color:var(--text-muted);">${h.season_number ? `S${h.season_number}E${h.episode_number}` : '电影'}</td>
                <td>${Math.floor(h.watch_seconds/60)} 分钟</td>
                <td style="color:var(--text-muted);font-size:12px;">${(h.updated_at||'').substring(0,16)}</td>
            </tr>`).join('')}
        </tbody></table></div>`
        : '<div style="padding:40px;text-align:center;color:var(--text-muted);">该用户暂无观看历史</div>';
    showAlert('用户观看历史 (ID: ' + uid + ')', html);
}
async function viewUserFavorites(uid) {
    const r = await (await fetch(API + `?action=user_favorites&user_id=${uid}`)).json();
    const html = r.success && r.list.length
        ? `<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:12px;max-height:500px;overflow:auto;">
            ${r.list.map(f => `
            <div style="background:var(--bg-input);border-radius:var(--radius-md);overflow:hidden;">
                <div style="aspect-ratio:2/3;background-image:url('${escapeHtml(f.poster)}');background-size:cover;background-position:center;"></div>
                <div style="padding:8px;font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${escapeHtml(f.title)}</div>
            </div>`).join('')}
        </div>`
        : '<div style="padding:40px;text-align:center;color:var(--text-muted);">该用户暂无收藏</div>';
    showAlert('用户收藏 (ID: ' + uid + ')', html);
}

// ========== 观看历史模块 ==========
async function renderHistory() {
    const wrap = document.getElementById('panel-history');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title">观看历史模块</h3>
            <div><input class="form-input" id="historyUid" placeholder="输入用户ID" style="width:160px;height:40px;display:inline-block;vertical-align:middle;">
            <button class="btn btn-primary btn-sm" style="vertical-align:middle;margin-left:8px;" onclick="renderHistoryForUser(parseInt(document.getElementById('historyUid').value||0))">查看指定用户</button>
            </div>
        </div>
        <div class="admin-panel-body" id="historyBody">
            <div style="padding:20px;color:var(--text-secondary);">请在上方用户管理中点击某用户的"历史"按钮，或输入用户ID查看。</div>
        </div>
    </div>`;
}
async function renderHistoryForUser(uid) {
    const body = document.getElementById('historyBody');
    if (!uid) { showToast('请输入正确的用户ID','warning'); return; }
    body.innerHTML = '<div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div>';
    viewUserHistory(uid);
    const r = await (await fetch(API + `?action=user_history&user_id=${uid}`)).json();
    if (r.success && r.list.length) {
        body.innerHTML = `<div class="admin-panel" style="box-shadow:none;border:none;padding:0;margin:0;">
        <div class="admin-panel-body" style="padding:0;"><table class="data-table">
        <thead><tr><th>ID</th><th>影视</th><th>类型/季/集</th><th>观看时长</th><th>进度</th><th>更新时间</th></tr></thead>
        <tbody>${r.list.map(h => `<tr>
            <td>${h.tmdb_id}</td>
            <td style="display:flex;align-items:center;gap:10px;">
                ${h.poster?`<img src="${escapeHtml(h.poster)}" style="width:40px;height:56px;object-fit:cover;border-radius:4px;">`:''}
                <span style="font-weight:500;">${escapeHtml(h.title)}</span>
            </td>
            <td>${h.type==='movie'?'电影':`剧集 S${h.season_number||'-'}E${h.episode_number||'-'}`}</td>
            <td>${Math.floor(h.watch_seconds/60)} 分 ${h.watch_seconds%60} 秒</td>
            <td>${h.last_position?Math.floor(h.last_position/60)+':'+String(h.last_position%60).padStart(2,'0'):'-'}</td>
            <td style="color:var(--text-muted);font-size:12px;">${(h.updated_at||'').substring(0,16)}</td>
        </tr>`).join('')}</tbody>
        </table></div></div>`;
    } else body.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">无观看历史数据</div>';
}

// ========== 用户收藏模块 ==========
async function renderFavorites() {
    const wrap = document.getElementById('panel-favorites');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header">
            <h3 class="admin-panel-title">用户收藏模块</h3>
            <div><input class="form-input" id="favUid" placeholder="输入用户ID" style="width:160px;height:40px;display:inline-block;vertical-align:middle;">
            <button class="btn btn-primary btn-sm" style="vertical-align:middle;margin-left:8px;" onclick="renderFavoritesForUser(parseInt(document.getElementById('favUid').value||0))">查看指定用户</button>
            </div>
        </div>
        <div class="admin-panel-body" id="favBody">
            <div style="padding:20px;color:var(--text-secondary);">请在上方用户管理中点击某用户的"收藏"按钮，或输入用户ID查看。</div>
        </div>
    </div>`;
}
async function renderFavoritesForUser(uid) {
    const body = document.getElementById('favBody');
    if (!uid) { showToast('请输入正确的用户ID','warning'); return; }
    body.innerHTML = '<div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div>';
    const r = await (await fetch(API + `?action=user_favorites&user_id=${uid}`)).json();
    if (r.success && r.list.length) {
        body.innerHTML = `<div class="movie-grid">
            ${r.list.map(f => {
                const url = `/detail.php?type=${f.type}&id=${f.tmdb_id}`;
                return `<div class="movie-card" onclick="window.open('${url}')">
                    <div class="movie-poster"><img src="${escapeHtml(f.poster)}" onerror="this.style.opacity=0;"></div>
                    <div class="movie-info">
                        <div class="movie-title">${escapeHtml(f.title)}</div>
                        <div class="movie-subtitle"><span>${f.created_at.substring(0,10)}</span><span>·</span><span>${f.type==='movie'?'电影':'剧集'}</span></div>
                    </div>
                </div>`;
            }).join('')}
        </div>`;
    } else body.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">无收藏数据</div>';
}

// ========== 播放源管理 ==========
async function renderSources() {
    const wrap = document.getElementById('panel-sources');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header"><h3 class="admin-panel-title">播放源管理</h3><button class="btn btn-primary btn-sm" onclick="editSource(0)"><i class="icon icon-plus"></i> 新增播放源</button></div>
        <div class="admin-panel-body" id="sourcesBody"><div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div></div>
    </div>`;
    const r = await (await fetch(API + '?action=source_list')).json();
    const body = document.getElementById('sourcesBody');
    if (!r.success) { body.innerHTML = '加载失败'; return; }
    body.innerHTML = `<table class="data-table" style="margin:-20px -24px;width:calc(100% + 48px);">
        <thead><tr><th>ID</th><th>名称</th><th>类型</th><th>URL</th><th>排序</th><th>状态</th><th>操作</th></tr></thead>
        <tbody>${r.list.map(s => `<tr>
            <td>${s.id}</td>
            <td style="font-weight:600;">${escapeHtml(s.name)}</td>
            <td>${escapeHtml(s.type||'movie')}</td>
            <td style="max-width:280px;"><span style="color:var(--text-muted);font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;display:inline-block;max-width:280px;vertical-align:middle;">${escapeHtml(s.url)}</span></td>
            <td>${s.sort}</td>
            <td>${s.status ? '<span style="color:#10b981;"><span class="status-dot active"></span>启用</span>' : '<span style="color:#ef4444;"><span class="status-dot inactive"></span>禁用</span>'}</td>
            <td><div class="action-btns">
                <button class="action-btn edit" onclick="editSource(${s.id})">编辑</button>
                <button class="action-btn delete" onclick="deleteSource(${s.id})">删除</button>
            </div></td>
        </tr>`).join('')}</tbody>
    </table>`;
}
async function editSource(id) {
    let source = {id:0,name:'',url:'',type:'movie',sort:0,status:1};
    if (id) {
        const r = await (await fetch(API + '?action=source_list')).json();
        source = r.list.find(x => x.id === id) || source;
    }
    const html = `<div>
        <div class="form-grid">
            <div><label class="form-label">名称</label><input type="text" id="srcName" class="form-input" value="${escapeHtml(source.name)}"></div>
            <div><label class="form-label">类型</label><select class="form-select" id="srcType">
                <option value="movie" ${source.type==='movie'?'selected':''}>电影/通用</option>
                <option value="tv" ${source.type==='tv'?'selected':''}>剧集</option>
            </select></div>
        </div>
        <div class="form-group" style="margin-top:18px;"><label class="form-label">API地址 (默认 yyzy-tv)</label>
            <input type="text" id="srcUrl" class="form-input" value="${escapeHtml(source.url)}" placeholder="https://api.yyzy-tv.vip/inc/apijson.php"></div>
        <div class="form-grid">
            <div><label class="form-label">排序数字（越小越靠前）</label><input type="number" id="srcSort" class="form-input" value="${source.sort}"></div>
            <div><label class="form-label">状态</label><select class="form-select" id="srcStatus">
                <option value="1" ${source.status?'selected':''}>启用</option>
                <option value="0" ${!source.status?'selected':''}>禁用</option>
            </select></div>
        </div>
    </div>`;
    const btn = await showConfirm(id?'编辑播放源':'新增播放源', html, '保存');
    if (btn) {
        const name = document.getElementById('srcName').value.trim();
        const url = document.getElementById('srcUrl').value.trim();
        const type = document.getElementById('srcType').value;
        const sort = parseInt(document.getElementById('srcSort').value||0, 10);
        const status = parseInt(document.getElementById('srcStatus').value, 10);
        const r = await apiRequest(API, { action: 'save_source', id, name, url, type, sort, status });
        if (r.success) { showToast(r.message, 'success'); renderSources(); } else showToast(r.message, 'error');
    }
}
async function deleteSource(id) {
    if (!confirm('确认删除该播放源？')) return;
    const r = await apiRequest(API, { action: 'delete_source', id });
    if (r.success) { showToast(r.message, 'success'); renderSources(); }
}

// ========== 公告管理 ==========
async function renderAnnouncements() {
    const wrap = document.getElementById('panel-announcements');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header"><h3 class="admin-panel-title">公告管理</h3><button class="btn btn-primary btn-sm" onclick="editAnnouncement(0)"><i class="icon icon-plus"></i> 发布新公告</button></div>
        <div class="admin-panel-body" id="annBody"><div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div></div>
    </div>`;
    const r = await (await fetch(API + '?action=announcement_list')).json();
    const body = document.getElementById('annBody');
    if (!r.success) { body.innerHTML = '加载失败'; return; }
    body.innerHTML = `<table class="data-table" style="margin:-20px -24px;width:calc(100% + 48px);">
        <thead><tr><th>ID</th><th>标题</th><th>发布人</th><th>弹窗显示</th><th>更新时间</th><th>操作</th></tr></thead>
        <tbody>${r.list.map(a => `<tr>
            <td>${a.id}</td>
            <td style="font-weight:600;">${escapeHtml(a.title)}</td>
            <td>${escapeHtml(a.username||'')}</td>
            <td>${a.show_popup ? '<span style="color:#10b981;"><span class="status-dot active"></span>是</span>' : '<span style="color:var(--text-muted);">否</span>'}</td>
            <td style="color:var(--text-muted);font-size:12px;">${(a.updated_at||'').substring(0,16)}</td>
            <td><div class="action-btns">
                <button class="action-btn edit" onclick="editAnnouncement(${a.id})">编辑</button>
                <button class="action-btn delete" onclick="deleteAnnouncement(${a.id})">删除</button>
            </div></td>
        </tr>`).join('')}</tbody>
    </table>`;
}
async function editAnnouncement(id) {
    let a = {id:0,title:'',content:'',show_popup:1};
    if (id) {
        const r = await (await fetch(API + '?action=announcement_list')).json();
        a = r.list.find(x => x.id === id) || a;
    }
    const html = `<div>
        <div class="form-group"><label class="form-label">公告标题</label><input type="text" id="annTitle" class="form-input" value="${escapeHtml(a.title)}" placeholder="例如：系统维护通知"></div>
        <div class="form-group"><label class="form-label">公告内容（支持换行）</label><textarea id="annContent" rows="7" class="form-textarea" placeholder="请输入公告内容...">${escapeHtml(a.content)}</textarea></div>
        <div class="form-group"><label class="dont-show-again" style="display:flex;">
            <input type="checkbox" id="annPopup" ${a.show_popup?'checked':''}><span class="checkbox-custom"></span><span>在所有用户首页弹窗显示该公告（未勾选过"不再提示"的用户）</span>
        </label></div>
    </div>`;
    const btn = await showConfirm(id?'编辑公告':'发布公告', html, id?'保存':'发布公告');
    if (btn) {
        const title = document.getElementById('annTitle').value.trim();
        const content = document.getElementById('annContent').value.trim();
        const show_popup = document.getElementById('annPopup').checked ? 1 : 0;
        const r = await apiRequest(API, { action: 'save_announcement', id, title, content, show_popup });
        if (r.success) { showToast(r.message, 'success'); renderAnnouncements(); } else showToast(r.message, 'error');
    }
}
async function deleteAnnouncement(id) {
    if (!confirm('确认删除该公告？')) return;
    const r = await apiRequest(API, { action: 'delete_announcement', id });
    if (r.success) { showToast(r.message, 'success'); renderAnnouncements(); }
}

// ========== 反馈管理 ==========
async function renderFeedbacks() {
    const wrap = document.getElementById('panel-feedbacks');
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header"><h3 class="admin-panel-title">反馈管理</h3></div>
        <div class="admin-panel-body" id="fbBody"><div style="padding:30px;text-align:center;"><div class="loading-spinner"></div></div></div>
    </div>`;
    const r = await (await fetch(API + '?action=feedback_list')).json();
    const body = document.getElementById('fbBody');
    if (!r.success) { body.innerHTML = '加载失败'; return; }
    const stxt = {pending:'待处理',replied:'已回复',resolved:'已解决',closed:'已关闭'};
    body.innerHTML = `<table class="data-table" style="margin:-20px -24px;width:calc(100% + 48px);">
        <thead><tr><th>ID</th><th>用户</th><th>标题</th><th>内容</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
        <tbody>${r.list.map(f => `<tr>
            <td>${f.id}</td>
            <td style="font-weight:500;">${escapeHtml(f.username||'')}</td>
            <td style="max-width:180px;font-weight:600;">${escapeHtml(f.title)}</td>
            <td style="max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text-muted);font-size:12px;">${escapeHtml(f.content)}</td>
            <td><span class="status-badge status-${f.status||'pending'}">${stxt[f.status||'pending']||'待处理'}</span></td>
            <td style="color:var(--text-muted);font-size:12px;">${(f.created_at||'').substring(0,16)}</td>
            <td><div class="action-btns">
                <button class="action-btn view" onclick='replyFeedback(${JSON.stringify(f)})'>回复</button>
                <button class="action-btn edit" onclick="updateFbStatus(${f.id}, ${JSON.stringify(Object.keys(stxt))}, ${JSON.stringify(Object.values(stxt))})">状态</button>
                <button class="action-btn delete" onclick="deleteFb(${f.id})">删除</button>
            </div></td>
        </tr>`).join('')}</tbody>
    </table>`;
}
async function replyFeedback(f) {
    const html = `<div>
        <div style="background:var(--bg-input);border-radius:var(--radius-md);padding:14px;margin-bottom:16px;">
            <div style="font-weight:700;margin-bottom:6px;">${escapeHtml(f.title)} <span style="color:var(--text-muted);font-size:12px;font-weight:400;">· ${escapeHtml(f.username||'')}</span></div>
            <div style="color:var(--text-secondary);font-size:13px;line-height:1.7;white-space:pre-wrap;">${escapeHtml(f.content)}</div>
        </div>
        <div class="form-group"><label class="form-label">管理员回复（将高亮显示在最前）</label>
            <textarea id="adminReplyContent" rows="5" class="form-textarea" placeholder="请输入回复内容..."></textarea>
        </div>
    </div>`;
    const btn = await showConfirm('回复反馈 #' + f.id, html, '发送回复');
    if (btn) {
        const content = document.getElementById('adminReplyContent').value.trim();
        if (!content) { showToast('请输入回复内容','warning'); return; }
        const r = await apiRequest(API, { action: 'admin_reply_feedback', feedback_id: f.id, content });
        if (r.success) { showToast(r.message, 'success'); renderFeedbacks(); } else showToast(r.message, 'error');
    }
}
async function updateFbStatus(fid, keys, vals) {
    const html = `<div class="form-group"><label class="form-label">选择状态</label>
        <select class="form-select" id="fbStatusSel">${keys.map((k,i)=>`<option value="${k}">${vals[i]}</option>`).join('')}</select>
    </div>`;
    const btn = await showConfirm('更新反馈状态', html, '确定');
    if (btn) {
        const status = document.getElementById('fbStatusSel').value;
        const r = await apiRequest(API, { action: 'update_feedback_status', feedback_id: fid, status });
        if (r.success) { showToast('状态已更新', 'success'); renderFeedbacks(); }
    }
}
async function deleteFb(id) {
    if (!confirm('确认删除该反馈及其所有回复？')) return;
    const r = await apiRequest(API, { action: 'delete_feedback', id });
    if (r.success) { showToast('已删除', 'success'); renderFeedbacks(); }
}

// ========== 网站设置 ==========
async function renderSettings() {
    const wrap = document.getElementById('panel-settings');
    const currentColor = window.__THEME_COLOR;
    const currentParse = <?= json_encode(get_setting('player_parse_url', 'https://svip.ffzyplay.com/?url=')) ?>;
    const currentApi = <?= json_encode(get_setting('tmdb_api_key', 'cb44223c5dee5676ed3a839f42ed27e3')) ?>;
    const currentToken = <?= json_encode(get_setting('tmdb_read_token', '')) ?>;
    wrap.innerHTML = `
    <div class="admin-panel">
        <div class="admin-panel-header"><h3 class="admin-panel-title">🎨 网站主题颜色</h3></div>
        <div class="admin-panel-body">
            <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
                <label class="form-label" style="margin:0;">选择主题色：</label>
                <input type="color" id="themeColor" value="${currentColor}" style="width:60px;height:46px;border:none;background:transparent;border-radius:var(--radius-md);cursor:pointer;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    ${['#6366f1','#8b5cf6','#ec4899','#ef4444','#f59e0b','#10b981','#14b8a6','#0ea5e9','#1e293b'].map(c => `<button onclick="document.getElementById('themeColor').value='${c}'" style="width:36px;height:36px;border-radius:50%;background:${c};border:${c===currentColor?'3px solid #fff':'2px solid var(--border-color)'};box-shadow:${c===currentColor?'0 0 0 2px var(--theme-color)':'none'};"></button>`).join('')}
                </div>
                <button class="btn btn-primary btn-sm" onclick="saveThemeColor()">保存主题</button>
                <span style="color:var(--text-muted);font-size:12px;">（保存后刷新页面生效）</span>
            </div>
        </div>
    </div>

    <div class="admin-panel" style="margin-top:24px;">
        <div class="admin-panel-header"><h3 class="admin-panel-title">🎬 解析播放器地址</h3></div>
        <div class="admin-panel-body">
            <div class="form-group">
                <label class="form-label">解析器前缀地址（用于拼接播放源直链播放）</label>
                <input type="text" id="parseUrl" class="form-input" value="${escapeHtml(currentParse)}" placeholder="https://svip.ffzyplay.com/?url=">
            </div>
            <button class="btn btn-primary btn-sm" onclick="saveParseUrl()">保存解析器地址</button>
        </div>
    </div>

    <div class="admin-panel" style="margin-top:24px;">
        <div class="admin-panel-header"><h3 class="admin-panel-title">📽️ TMDB API 配置</h3></div>
        <div class="admin-panel-body">
            <div class="form-grid">
                <div><label class="form-label">TMDB API Key (v3)</label><input type="text" id="tmdbKey" class="form-input" value="${escapeHtml(currentApi)}"></div>
                <div><label class="form-label">TMDB Read Token (v4)</label><input type="text" id="tmdbToken" class="form-input" value="${escapeHtml(currentToken)}"></div>
            </div>
            <div style="margin-top:16px;"><button class="btn btn-primary btn-sm" onclick="saveTmdb()">保存TMDB配置</button></div>
        </div>
    </div>

    <div class="admin-panel" style="margin-top:24px;">
        <div class="admin-panel-header"><h3 class="admin-panel-title">📧 邮件SMTP配置</h3></div>
        <div class="admin-panel-body">
            <div class="need-login-tip" style="margin-bottom:0;">
                <div class="need-login-icon" style="background:var(--theme-color);"><i class="icon icon-mail"></i></div>
                <div class="need-login-text">
                    <h4>SMTP 配置已内置</h4>
                    <p>Host: smtp.163.com &nbsp;·&nbsp; 端口: 465(SSL) &nbsp;·&nbsp; 发件人: jtxnb886@163.com &nbsp;·&nbsp; Jay影视<br>
                        如需修改请直接编辑根目录下 <code style="background:#000;padding:2px 6px;border-radius:4px;">config.php</code> 文件</p>
                </div>
            </div>
        </div>
    </div>
    `;
}
async function saveThemeColor() {
    const color = document.getElementById('themeColor').value;
    const r = await apiRequest(API, { action: 'save_theme_color', color });
    if (r.success) { showToast(r.message + '，页面即将刷新...', 'success'); setTimeout(() => location.reload(), 1000); } else showToast(r.message, 'error');
}
async function saveParseUrl() {
    const url = document.getElementById('parseUrl').value.trim();
    if (!url) { showToast('地址不能为空', 'warning'); return; }
    const r = await apiRequest(API, { action: 'save_player_parse_url', url });
    if (r.success) showToast(r.message, 'success'); else showToast(r.message, 'error');
}
async function saveTmdb() {
    const api_key = document.getElementById('tmdbKey').value.trim();
    const read_token = document.getElementById('tmdbToken').value.trim();
    if (!api_key) { showToast('API Key 不能为空', 'warning'); return; }
    const r = await apiRequest(API, { action: 'save_tmdb_key', api_key, read_token });
    if (r.success) showToast(r.message + '，下次请求生效', 'success'); else showToast(r.message, 'error');
}

// ========== 自定义确认对话框 ==========
function showConfirm(title, bodyHtml, okText = '确定') {
    return new Promise(resolve => {
        let m = document.getElementById('adminModal');
        if (!m) {
            m = document.createElement('div');
            m.id = 'adminModal';
            m.className = 'modal-overlay';
            document.body.appendChild(m);
            m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('show'); resolve(false); } });
        }
        m.innerHTML = `<div class="modal">
            <div class="modal-header">
                <div><div class="modal-title">${title}</div></div>
                <button class="modal-close" onclick="document.getElementById('adminModal').classList.remove('show')"><i class="icon icon-x"></i></button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom:20px;">${bodyHtml}</div>
                <div style="display:flex;gap:12px;justify-content:flex-end;">
                    <button class="btn btn-secondary" id="adminModalCancel">取消</button>
                    <button class="btn btn-primary" id="adminModalOk">${okText}</button>
                </div>
            </div>
        </div>`;
        m.classList.add('show');
        document.getElementById('adminModalCancel').onclick = () => { m.classList.remove('show'); resolve(false); };
        document.getElementById('adminModalOk').onclick = () => { resolve(true); };
    });
}
function showAlert(title, bodyHtml) {
    let m = document.getElementById('adminModal');
    if (!m) {
        m = document.createElement('div');
        m.id = 'adminModal';
        m.className = 'modal-overlay';
        document.body.appendChild(m);
        m.addEventListener('click', e => { if (e.target === m) { m.classList.remove('show'); } });
    }
    m.innerHTML = `<div class="modal">
        <div class="modal-header">
            <div><div class="modal-title">${title}</div></div>
            <button class="modal-close" onclick="document.getElementById('adminModal').classList.remove('show')"><i class="icon icon-x"></i></button>
        </div>
        <div class="modal-body">
            <div style="margin-bottom:20px;">${bodyHtml}</div>
            <div style="display:flex;justify-content:flex-end;"><button class="btn btn-primary" onclick="document.getElementById('adminModal').classList.remove('show')">关闭</button></div>
        </div>
    </div>`;
    m.classList.add('show');
}

// 初始化仪表盘
switchMenu('dashboard');
</script>
</body>
</html>
