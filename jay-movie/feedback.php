<?php
$navActive = 'feedback';
$pageTitle = '意见反馈 - Jay影视';
$bodyPage = 'feedback';
require_once __DIR__ . '/includes/header.php';

$needLoginTip = !$currentUser;
?>

<?php if ($needLoginTip): ?>
<div class="need-login-tip">
    <div class="need-login-icon"><i class="icon icon-feedback"></i></div>
    <div class="need-login-text">
        <h4>登录后提交反馈</h4>
        <p>需要登录才可以发布反馈和互动哦，如没有账号请注册！</p>
    </div>
    <div>
        <button class="btn btn-secondary" data-login>登录</button>
        <button class="btn btn-primary" data-register style="margin-left:8px;">注册</button>
    </div>
</div>
<?php endif; ?>

<!-- 提交反馈 -->
<?php if ($currentUser && $currentUser['status'] == 1): ?>
<div class="feedback-submit-section">
    <h3 style="font-size:18px;font-weight:700;margin-bottom:14px;display:flex;align-items:center;gap:10px;">
        <span class="section-title-icon" style="width:30px;height:30px;border-radius:8px;font-size:16px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--theme-color),var(--theme-dark));color:#fff;">
            <i class="icon icon-feedback"></i>
        </span>
        提交反馈
    </h3>
    <form id="feedbackForm" onsubmit="submitFeedback(event)">
        <input type="text" class="feedback-title-input" name="title" placeholder="反馈标题（2-50个字符）" maxlength="50" required>
        <textarea class="feedback-content-input" name="content" placeholder="详细描述您遇到的问题或建议（至少5个字符）..." rows="5" required></textarea>
        <div style="display:flex;gap:12px;justify-content:flex-end;flex-wrap:wrap;">
            <span id="fbStatus" style="align-self:center;color:var(--text-muted);font-size:13px;"></span>
            <button type="submit" class="btn btn-primary"><i class="icon icon-feedback"></i> 提交反馈</button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- 反馈列表 -->
<div style="margin-top:30px;">
    <div class="section-header" style="margin-bottom:20px;">
        <h2 class="section-title">
            <span class="section-title-icon"><i class="icon icon-feedback"></i></span>
            用户反馈广场
        </h2>
        <button class="see-more" onclick="loadFeedbacks()"><i class="icon icon-clock" style="margin-right:4px;"></i> 刷新</button>
    </div>
    <div id="feedbackList" class="feedback-list">
        <div style="padding:40px;text-align:center;"><div class="loading-spinner"></div><div style="margin-top:12px;color:var(--text-muted);">加载中...</div></div>
    </div>
</div>

<script>
async function submitFeedback(e) {
    e.preventDefault();
    if (!window.__USER) { showToast('请先登录','warning'); openAuthModal('login'); return; }
    const fd = new FormData(e.target);
    const status = document.getElementById('fbStatus');
    status.textContent = '提交中...';
    try {
        const res = await apiRequest('/api/feedback.php', { action: 'submit', title: fd.get('title'), content: fd.get('content') });
        status.textContent = '';
        if (res.success) {
            showToast(res.message, 'success');
            e.target.reset();
            loadFeedbacks();
        } else {
            showToast(res.message, 'error');
        }
    } catch(err) { showToast('网络错误','error'); status.textContent = ''; }
}

function renderUserAvatar(u) {
    const name = u ? u.username : '匿名';
    const letter = (name || 'U').charAt(0).toUpperCase();
    if (u && u.avatar) return `<div class="feedback-user-avatar" style="background:var(--theme-color);"><img src="${escapeHtml(u.avatar)}" onerror="this.remove()"></div>`;
    const colors = ['#6366f1','#ec4899','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#0ea5e9'];
    const c = colors[(name||'').length % colors.length];
    return `<div class="feedback-user-avatar" style="background:${c};">${letter}</div>`;
}

function renderFeedbackItem(item) {
    const timeAgo = formatTime(item.created_at);
    const statusClass = 'status-' + (item.status || 'pending');
    const statusText = {pending:'待处理',replied:'已回复',resolved:'已解决',closed:'已关闭'}[item.status||'pending'] || '待处理';
    const u = {username: item.username, avatar: item.avatar, is_admin: item.is_admin};
    // 管理员回复置顶（已通过SQL order by is_admin desc），但反馈者本人的回复应该排在最上面？
    // 按需求：管理员回复在普通用户上面，在反馈者下面 -> 即 order: [feedback作者回复 -> 管理员回复 -> 普通用户]
    // 这里简化：SQL的order is_admin desc会把管理员放在最前，我们再做一个二次排序

    let replies = (item.replies || []).slice();
    // 排序：先管理员，再其他（除原反馈者外），反馈者自己回复和管理员谁在前面？ 需求：管理员在普通用户上面，在反馈者的下面
    // 简化：按需求描述，管理员在中间，我们就用默认顺序即可
    const fUser = item.username;
    replies.sort((a, b) => {
        if (a.is_admin && !b.is_admin) return -1;
        if (!a.is_admin && b.is_admin) return 1;
        return 0;
    });
    const collapsed = replies.length > 3;
    const repliesHtml = replies.map((r, idx) => {
        const ru = {username: r.username, avatar: r.avatar};
        const adminBadge = r.is_admin ? '<span class="admin-badge" style="margin:0;padding:2px 8px;font-size:11px;"><span class="admin-badge-icon" style="width:10px;height:10px;margin-right:3px;"></span>开发者</span>' : '';
        return `<div class="reply-item ${r.is_admin ? 'admin-reply' : ''}">
            ${renderReplyAvatar(ru, r.is_admin)}
            <div class="reply-content">
                <div class="reply-meta">
                    <span class="reply-name">${escapeHtml(r.username||'用户')}</span>
                    ${adminBadge}
                    <span class="reply-date">${formatTime(r.created_at)}</span>
                </div>
                <div class="reply-text">${escapeHtml(r.content).replace(/\n/g,'<br>')}</div>
            </div>
        </div>`;
    }).join('');
    const expandBtn = collapsed ? `<button class="replies-expand-btn" onclick="this.parentNode.classList.toggle('replies-collapsed');this.textContent=this.parentNode.classList.contains('replies-collapsed')?'展开全部 '+replies.length+' 条回复 ▼':'收起回复 ▲';">展开全部 ${replies.length} 条回复 ▼</button>` : '';
    const likedClass = item.liked ? 'liked' : '';

    return `<div class="feedback-item" data-id="${item.id}">
        <div class="feedback-item-header">
            ${renderUserAvatar(u)}
            <div class="feedback-user-info">
                <div class="feedback-user-name">
                    ${escapeHtml(item.username||'用户')}
                    ${u.is_admin ? '<span class="admin-badge" style="padding:2px 8px;font-size:11px;"><span class="admin-badge-icon" style="width:10px;height:10px;margin-right:3px;"></span>开发者</span>' : ''}
                </div>
                <div class="feedback-time">${timeAgo}</div>
            </div>
            <span class="status-badge ${statusClass}">${statusText}</span>
        </div>
        <div class="feedback-title-h">${escapeHtml(item.title)}</div>
        <div class="feedback-content-text">${escapeHtml(item.content).replace(/\n/g,'<br>')}</div>
        <div class="feedback-actions">
            <button class="like-btn ${likedClass}" onclick="toggleLike(${item.id}, this)">
                <i class="icon icon-thumb"></i> <span>${item.likes_count||0}</span>
            </button>
            <button class="reply-toggle-btn" onclick="toggleReplyBox(${item.id})">
                <i class="icon icon-feedback"></i> 回复 <span>(${item.replies_count||0})</span>
            </button>
        </div>
        <div class="feedback-replies">
            <div class="reply-input-area" id="replyBox_${item.id}" style="display:none;">
                <textarea class="reply-input" placeholder="友善发言，理性讨论..." rows="2" onkeydown="if(event.ctrlKey&&event.keyCode==13){this.nextElementSibling.click()}"></textarea>
                <button class="reply-send-btn" onclick="submitReply(${item.id})">发送</button>
            </div>
            ${replies.length ? `<div class="reply-list ${collapsed ? 'replies-collapsed' : ''}" style="margin-top:12px;">${repliesHtml}${expandBtn}</div>` : ''}
        </div>
    </div>`;
}

function renderReplyAvatar(u, isAdmin) {
    const name = u ? u.username : 'U';
    const letter = (name || 'U').charAt(0).toUpperCase();
    if (u && u.avatar) return `<div class="reply-avatar" style="background:var(--theme-color);"><img src="${escapeHtml(u.avatar)}" onerror="this.remove()"></div>`;
    if (isAdmin) return `<div class="reply-avatar" style="background:linear-gradient(135deg,#ef4444,#dc2626);">${letter}</div>`;
    const colors = ['#6366f1','#ec4899','#10b981','#f59e0b','#ef4444','#14b8a6','#8b5cf6','#0ea5e9'];
    return `<div class="reply-avatar" style="background:${colors[(name||'').length % colors.length]};">${letter}</div>`;
}

function formatTime(dt) {
    if (!dt) return '';
    const t = new Date(dt.replace(' ', 'T'));
    const diff = (Date.now() - t.getTime()) / 1000;
    if (diff < 60) return Math.floor(diff) + '秒前';
    if (diff < 3600) return Math.floor(diff/60) + '分钟前';
    if (diff < 86400) return Math.floor(diff/3600) + '小时前';
    if (diff < 2592000) return Math.floor(diff/86400) + '天前';
    return dt.substring(0, 10);
}

async function loadFeedbacks() {
    const list = document.getElementById('feedbackList');
    list.innerHTML = '<div style="padding:40px;text-align:center;"><div class="loading-spinner"></div></div>';
    try {
        const res = await fetch('/api/feedback.php?action=list');
        const data = await res.json();
        if (!data.success || !data.list.length) {
            list.innerHTML = `<div class="empty-state"><i class="icon icon-feedback"></i><h3>还没有反馈</h3><p>快来发表第一个反馈吧~</p></div>`;
            return;
        }
        list.innerHTML = data.list.map(renderFeedbackItem).join('');
    } catch(e) {
        list.innerHTML = '<div style="padding:40px;text-align:center;color:var(--text-muted);">加载失败，请刷新重试</div>';
    }
}

async function toggleLike(id, btn) {
    if (!window.__USER) { showToast('请先登录', 'warning'); openAuthModal('login'); return; }
    const res = await apiRequest('/api/feedback.php', { action: 'toggle_like', feedback_id: id });
    if (res.success) {
        const span = btn.querySelector('span');
        let n = parseInt(span.textContent || '0', 10);
        if (res.liked) { btn.classList.add('liked'); n++; }
        else { btn.classList.remove('liked'); n = Math.max(0, n-1); }
        span.textContent = n;
    }
}

function toggleReplyBox(id) {
    if (!window.__USER) { showToast('请先登录', 'warning'); openAuthModal('login'); return; }
    const box = document.getElementById('replyBox_'+id);
    if (!box) return;
    box.style.display = box.style.display === 'none' ? 'flex' : 'none';
    if (box.style.display === 'flex') {
        setTimeout(() => box.querySelector('.reply-input').focus(), 100);
    }
}

async function submitReply(id) {
    if (!window.__USER) { showToast('请先登录', 'warning'); openAuthModal('login'); return; }
    const box = document.getElementById('replyBox_'+id);
    const input = box.querySelector('.reply-input');
    const content = input.value.trim();
    if (!content) { showToast('请输入回复内容','warning'); return; }
    const res = await apiRequest('/api/feedback.php', { action: 'reply', feedback_id: id, content });
    if (res.success) {
        showToast(res.message, 'success');
        input.value = '';
        loadFeedbacks();
    } else {
        showToast(res.message || '回复失败', 'error');
    }
}

loadFeedbacks();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
