/* ============ Jay影视 前端核心JS ============ */
const THEME_COLOR = (typeof window.__THEME_COLOR !== 'undefined') ? window.__THEME_COLOR : '#6366f1';
document.documentElement.style.setProperty('--theme-color', THEME_COLOR);
document.documentElement.style.setProperty('--theme-dark', adjustColor(THEME_COLOR, -10));
document.documentElement.style.setProperty('--theme-light', adjustColor(THEME_COLOR, 10));

function adjustColor(hex, percent) {
    const num = parseInt(hex.replace('#', ''), 16);
    const r = Math.min(255, Math.max(0, (num >> 16) + Math.round(255 * percent / 100)));
    const g = Math.min(255, Math.max(0, ((num >> 8) & 0xff) + Math.round(255 * percent / 100)));
    const b = Math.min(255, Math.max(0, (num & 0xff) + Math.round(255 * percent / 100)));
    return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
}

// ============ Toast ============
function showToast(message, type = 'info', duration = 3000) {
    if (!document.querySelector('.toast-container')) {
        const c = document.createElement('div');
        c.className = 'toast-container';
        document.body.appendChild(c);
    }
    const container = document.querySelector('.toast-container');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    const iconMap = { success: '✓', error: '✕', info: 'ℹ', warning: '⚠' };
    toast.innerHTML = `<span style="font-size:16px;">${iconMap[type] || 'ℹ'}</span><span>${message}</span>`;
    container.appendChild(toast);
    setTimeout(() => toast.classList.add('show'), 10);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ============ AJAX Helpers ============
async function apiRequest(url, data, method = 'POST') {
    const options = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    if (data instanceof FormData) {
        options.body = data;
    } else if (data) {
        options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
        options.body = new URLSearchParams(data).toString();
    }
    const res = await fetch(url, options);
    return await res.json();
}

// ============ Current User ============
let currentUserCache = null;
async function getCurrentUser() {
    if (currentUserCache) return currentUserCache;
    try {
        const res = await apiRequest('/api/auth.php', { action: 'current_user' });
        if (res.success) {
            currentUserCache = res.user;
            document.documentElement.style.setProperty('--theme-color', res.user.theme_color || THEME_COLOR);
        }
    } catch (e) {}
    return currentUserCache;
}

function requireLogin(callback) {
    getCurrentUser().then(user => {
        if (user && user.status == 1) {
            callback(user);
        } else {
            showToast('需要登录才可以观看哦，如没有账号请注册！', 'warning');
            openAuthModal('login');
        }
    });
}

// ============ Header UI ============
function initHeaderUI() {
    // User dropdown
    const avatar = document.querySelector('.user-avatar');
    const dropdown = document.querySelector('.user-dropdown');
    if (avatar && dropdown) {
        avatar.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });
        document.addEventListener('click', () => dropdown.classList.remove('show'));
    }

    // Logout
    document.querySelectorAll('[data-logout]').forEach(el => {
        el.addEventListener('click', async () => {
            await apiRequest('/api/auth.php', { action: 'logout' });
            currentUserCache = null;
            location.reload();
        });
    });

    // Mobile menu toggle
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    if (mobileBtn) {
        mobileBtn.addEventListener('click', () => {
            const nav = document.querySelector('.nav-links');
            if (nav) {
                nav.style.cssText = nav.style.display === 'flex' ? '' :
                    'display:flex;position:absolute;top:70px;left:0;right:0;background:var(--bg-card);flex-direction:column;padding:14px;gap:2px;z-index:50;border-bottom:1px solid var(--border-color);';
            }
        });
    }
}

// ============ Auth Modal ============
let authMode = 'login';
function openAuthModal(mode = 'login') {
    authMode = mode;
    let modal = document.getElementById('authModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'authModal';
        modal.className = 'modal-overlay';
        modal.innerHTML = renderAuthModalHTML();
        document.body.appendChild(modal);
        bindAuthModalEvents(modal);
    }
    const loginBox = modal.querySelector('#auth-login');
    const regBox = modal.querySelector('#auth-register');
    if (mode === 'register') {
        loginBox.style.display = 'none';
        regBox.style.display = 'block';
    } else {
        loginBox.style.display = 'block';
        regBox.style.display = 'none';
    }
    modal.classList.add('show');
}
function closeAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) modal.classList.remove('show');
}
function renderAuthModalHTML() {
    return `
    <div class="modal">
        <div class="modal-header">
            <div>
                <div class="modal-title" id="authTitle">欢迎登录</div>
                <div class="modal-subtitle" id="authSubtitle">登录后即可观看所有影视内容</div>
            </div>
            <button class="modal-close" onclick="closeAuthModal()"><i class="icon icon-x"></i></button>
        </div>
        <div class="modal-body">
            <div id="auth-login">
                <form id="loginForm">
                    <div class="form-group">
                        <label class="form-label">用户名 / 邮箱</label>
                        <input type="text" name="account" class="form-input" placeholder="请输入用户名或邮箱" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">密码</label>
                        <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
                    </div>
                    <div class="form-error" id="loginError"></div>
                    <button type="submit" class="btn btn-primary form-submit-btn">登 录</button>
                </form>
                <div class="auth-switch">
                    还没有账号？<a onclick="switchAuthMode('register')">立即注册</a>
                </div>
            </div>
            <div id="auth-register" style="display:none;">
                <form id="registerForm">
                    <div class="form-group">
                        <label class="form-label">邮箱</label>
                        <input type="email" name="email" class="form-input" placeholder="请输入您的邮箱" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">用户名</label>
                        <input type="text" name="username" class="form-input" placeholder="请输入用户名 (2-20字符)" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">设置密码</label>
                        <input type="password" name="password" class="form-input" placeholder="至少6位" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">确认密码</label>
                        <input type="password" name="password2" class="form-input" placeholder="再次输入密码" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">邮箱验证码</label>
                        <div class="form-row">
                            <input type="text" name="code" class="form-input" placeholder="输入验证码" maxlength="6" required>
                            <button type="button" class="form-btn" id="sendCodeBtn">获取验证码</button>
                        </div>
                        <div class="form-success" id="codeSuccess">验证码已发送，请检查邮箱！</div>
                    </div>
                    <div class="form-error" id="registerError"></div>
                    <button type="submit" class="btn btn-primary form-submit-btn">注 册</button>
                </form>
                <div class="auth-switch">
                    已有账号？<a onclick="switchAuthMode('login')">直接登录</a>
                </div>
            </div>
        </div>
    </div>`;
}
function switchAuthMode(mode) {
    const modal = document.getElementById('authModal');
    if (!modal) return;
    const loginBox = modal.querySelector('#auth-login');
    const regBox = modal.querySelector('#auth-register');
    const title = modal.querySelector('#authTitle');
    const sub = modal.querySelector('#authSubtitle');
    if (mode === 'register') {
        loginBox.style.display = 'none';
        regBox.style.display = 'block';
        title.textContent = '注册账号';
        sub.textContent = '完成注册，开启精彩影视之旅';
    } else {
        loginBox.style.display = 'block';
        regBox.style.display = 'none';
        title.textContent = '欢迎登录';
        sub.textContent = '登录后即可观看所有影视内容';
    }
    authMode = mode;
}
function bindAuthModalEvents(modal) {
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeAuthModal();
    });
    modal.querySelector('#loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const err = modal.querySelector('#loginError');
        err.style.display = 'none';
        try {
            const res = await apiRequest('/api/auth.php', { action: 'login', account: fd.get('account'), password: fd.get('password') });
            if (res.success) {
                currentUserCache = null;
                showToast(res.message, 'success');
                closeAuthModal();
                setTimeout(() => location.reload(), 600);
            } else {
                err.textContent = res.message;
                err.style.display = 'block';
            }
        } catch (err) {
            showToast('网络错误，请稍后重试', 'error');
        }
    });
    modal.querySelector('#sendCodeBtn').addEventListener('click', async function () {
        const email = modal.querySelector('input[name="email"]').value.trim();
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showToast('请输入正确的邮箱地址', 'warning');
            return;
        }
        this.disabled = true;
        const origText = this.textContent;
        this.textContent = '发送中...';
        try {
            const res = await apiRequest('/api/auth.php', { action: 'send_code', email, type: 'register' });
            if (res.success) {
                modal.querySelector('#codeSuccess').style.display = 'block';
                let t = 60;
                const timer = setInterval(() => {
                    t--;
                    this.textContent = `${t}s 后重发`;
                    if (t <= 0) { clearInterval(timer); this.disabled = false; this.textContent = '重新获取'; }
                }, 1000);
                showToast(res.message, 'success');
            } else {
                this.disabled = false;
                this.textContent = origText;
                showToast(res.message, 'error');
            }
        } catch (e) {
            this.disabled = false;
            this.textContent = origText;
            showToast('网络错误', 'error');
        }
    });
    modal.querySelector('#registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const fd = new FormData(e.target);
        const err = modal.querySelector('#registerError');
        err.style.display = 'none';
        try {
            const res = await apiRequest('/api/auth.php', {
                action: 'register',
                email: fd.get('email'),
                username: fd.get('username'),
                password: fd.get('password'),
                password2: fd.get('password2'),
                code: fd.get('code')
            });
            if (res.success) {
                showToast(res.message, 'success');
                closeAuthModal();
                setTimeout(() => location.reload(), 600);
            } else {
                err.textContent = res.message;
                err.style.display = 'block';
            }
        } catch (e) {
            showToast('网络错误，请稍后重试', 'error');
        }
    });
}

// ============ Announcement Modal ============
async function checkAndShowAnnouncement() {
    try {
        const res = await fetch('/api/site.php?action=get_latest_announcement');
        const data = await res.json();
        if (data.success && data.announcement) {
            const key = `jay_dismiss_ann_${data.announcement.id}`;
            if (!localStorage.getItem(key)) {
                showAnnouncementModal(data.announcement);
            }
        }
    } catch (e) {}
}
function showAnnouncementModal(ann) {
    let modal = document.getElementById('announcementModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'announcementModal';
        modal.className = 'modal-overlay announcement-modal';
        document.body.appendChild(modal);
    }
    modal.innerHTML = `
    <div class="modal">
        <button class="modal-close" style="position:absolute;top:20px;right:20px;color:#fff;font-size:22px;z-index:5;" onclick="closeAnnouncement()"><i class="icon icon-x"></i></button>
        <div class="announcement-header">
            <div class="announcement-icon"><i class="icon icon-bell"></i></div>
            <h2>${escapeHtml(ann.title)}</h2>
        </div>
        <div class="announcement-body-content">
            <div class="announcement-content">${ann.content.replace(/\n/g, '<br>')}</div>
        </div>
        <div class="announcement-footer">
            <label class="dont-show-again">
                <input type="checkbox" id="dontShowAgain">
                <span class="checkbox-custom"></span>
                <span>不再显示此公告</span>
            </label>
            <button class="btn btn-primary" onclick="closeAnnouncement(${ann.id})">我知道了</button>
        </div>
    </div>`;
    modal.classList.add('show');
}
function closeAnnouncement(annId = null) {
    const modal = document.getElementById('announcementModal');
    if (!modal) return;
    const checkbox = modal.querySelector('#dontShowAgain');
    if (annId && checkbox && checkbox.checked) {
        localStorage.setItem(`jay_dismiss_ann_${annId}`, '1');
        getCurrentUser().then(user => {
            if (user) {
                apiRequest('/api/site.php', { action: 'dismiss_announcement', announcement_id: annId });
            }
        });
    }
    modal.classList.remove('show');
}
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

// ============ Horizontal scroll buttons ============
function initHorizontalScroll() {
    document.querySelectorAll('.horizontal-scroll-wrapper').forEach(wrapper => {
        const scroll = wrapper.querySelector('.horizontal-scroll');
        const prev = wrapper.querySelector('.scroll-btn.prev');
        const next = wrapper.querySelector('.scroll-btn.next');
        if (!scroll) return;
        if (prev) prev.addEventListener('click', () => scroll.scrollBy({ left: -scroll.clientWidth * 0.8, behavior: 'smooth' }));
        if (next) next.addEventListener('click', () => scroll.scrollBy({ left: scroll.clientWidth * 0.8, behavior: 'smooth' }));
    });
}

// ============ Search ============
function initSearch() {
    const input = document.querySelector('.search-input');
    const overlay = document.getElementById('searchResultsOverlay');
    if (!input) return;
    
    if (!overlay) {
        const wrap = document.createElement('div');
        wrap.className = 'search-box';
        input.parentNode.appendChild(wrap);
        wrap.style.position = 'relative';
        wrap.appendChild(input);
        const o = document.createElement('div');
        o.id = 'searchResultsOverlay';
        o.className = 'search-results-overlay';
        wrap.appendChild(o);
        if (input.previousElementSibling) {
            const si = input.previousElementSibling;
            if (si.classList.contains('search-icon')) wrap.insertBefore(si, input);
        }
    }
    let timer = null;
    input.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(doSearch, 400);
    });
    input.addEventListener('focus', doSearch);
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-box')) {
            document.getElementById('searchResultsOverlay').classList.remove('show');
        }
    });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const q = input.value.trim();
            if (q) location.href = `/search.php?q=${encodeURIComponent(q)}`;
        }
    });

    async function doSearch() {
        const q = input.value.trim();
        const ov = document.getElementById('searchResultsOverlay');
        if (!q) { ov.classList.remove('show'); return; }
        try {
            const res = await fetch(`/api/tmdb.php?action=search&q=${encodeURIComponent(q)}`);
            const data = await res.json();
            let html = '';
            if (data.success && data.results && data.results.length > 0) {
                data.results.slice(0, 8).forEach(item => {
                    const type = item.media_type || (item.title ? 'movie' : 'tv');
                    const title = item.title || item.name;
                    const date = item.release_date || item.first_air_date || '';
                    const year = date ? date.substring(0, 4) : '';
                    const poster = item.poster_path ? `https://image.tmdb.org/t/p/w200${item.poster_path}` : '';
                    const url = `/detail.php?type=${type}&id=${item.id}`;
                    html += `<div class="search-result-item" onclick="location.href='${url}'">
                        <img class="search-result-poster" src="${poster}" onerror="this.style.visibility='hidden'">
                        <div class="search-result-info">
                            <div class="search-result-title">${escapeHtml(title)}</div>
                            <div class="search-result-meta">${year} · ${type === 'movie' ? '电影' : '剧集'} ${item.vote_average ? '· ★ ' + item.vote_average.toFixed(1) : ''}</div>
                        </div>
                    </div>`;
                });
            } else {
                html = `<div style="padding:30px;text-align:center;color:var(--text-muted);">未找到相关内容</div>`;
            }
            html += `<div style="padding:10px 16px;border-top:1px solid var(--border-color);text-align:center;"><a href="/search.php?q=${encodeURIComponent(q)}" style="color:var(--theme-color);font-size:13px;font-weight:600;">查看全部搜索结果 →</a></div>`;
            ov.innerHTML = html;
            ov.classList.add('show');
        } catch (e) {}
    }
}

// ============ Init ============
document.addEventListener('DOMContentLoaded', () => {
    initHeaderUI();
    initHorizontalScroll();
    initSearch();
    if (document.body.dataset.page === 'home' || !document.body.dataset.page) {
        checkAndShowAnnouncement();
    }
    document.querySelectorAll('[data-login]').forEach(el => {
        el.addEventListener('click', () => openAuthModal('login'));
    });
    document.querySelectorAll('[data-register]').forEach(el => {
        el.addEventListener('click', () => openAuthModal('register'));
    });
});
