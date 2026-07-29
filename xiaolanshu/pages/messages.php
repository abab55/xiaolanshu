<?php if (!isLoggedIn()): ?>
<div class="messages-page">
    <div class="msg-login-prompt">
        <div class="msg-login-icon">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        </div>
        <p class="msg-login-text">登录后查看消息</p>
        <a href="index.php?page=login" class="msg-login-btn">立即登录</a>
    </div>
</div>
<?php else: ?>
<div class="messages-page">
    <div class="messages-list" id="msgList">
        <div class="msg-loading">加载中...</div>
    </div>
    <div class="suggested-users-section" id="suggestedUsers">
        <h3>可能感兴趣的人</h3>
        <div class="suggested-users-list" id="suggestedUsersList">
            <span class="loading-text">加载中...</span>
        </div>
    </div>
</div>

<!-- + 按钮菜单浮层（由header中的+按钮触发） -->
<div class="msg-plus-menu" id="msgPlusMenu" style="display:none">
    <div class="msg-plus-item" onclick="location.href='index.php?page=qrcode'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><path d="M14 14h3v3m0 4v-7h-7"/></svg>
        <span>我的二维码</span>
    </div>
    <div class="msg-plus-item" onclick="location.href='index.php?page=create-group'">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <span>创建群聊</span>
    </div>
    <div class="msg-plus-item" onclick="showAddFriendGlobal()">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
        <span>添加好友</span>
    </div>
</div>

<!-- 添加好友弹窗 -->
<div class="fr-modal-overlay" id="frGlobalModal" style="display:none" onclick="if(event.target===this)hideFRGlobal()">
    <div class="fr-modal">
        <div class="fr-modal-header">
            <span>添加好友</span>
            <button class="fr-modal-close" onclick="hideFRGlobal()">&times;</button>
        </div>
        <div class="fr-modal-body">
            <div id="frSearchSection">
                <p class="fr-modal-desc">输入对方的用户名或ID</p>
                <input type="text" id="frSearchInput" class="fr-modal-input" placeholder="用户名或ID">
                <div id="frSearchResult" style="margin-top:10px"></div>
            </div>
            <div id="frDirectSection" style="display:none">
                <p class="fr-modal-desc">发送好友申请给 <strong id="frTargetName"></strong></p>
                <textarea id="frMessage" class="fr-modal-input" placeholder="写个验证消息吧..." maxlength="200" rows="3" style="height:80px;resize:none"></textarea>
            </div>
        </div>
        <div class="fr-modal-footer" id="frDirectFooter" style="display:none">
            <button class="fr-modal-btn cancel" onclick="hideFRGlobal()">取消</button>
            <button class="fr-modal-btn confirm" onclick="sendFriendRequestFromModal()">发送</button>
        </div>
    </div>
</div>

<style>
.msg-plus-menu{position:fixed;top:44px;right:12px;z-index:100;background:#fff;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,0.12);overflow:hidden;min-width:140px}
.msg-plus-item{display:flex;align-items:center;gap:10px;padding:12px 16px;font-size:14px;color:#333;cursor:pointer;border-bottom:0.5px solid #f2f2f2}
.msg-plus-item:last-child{border-bottom:0}
.msg-plus-item:active{background:#f5f5f5}
.msg-plus-item span{white-space:nowrap}
.fr-modal-footer{display:flex;gap:10px;padding:12px 16px;border-top:1px solid #eee}
.fr-modal-btn{border:none;border-radius:6px;padding:8px 20px;font-size:14px;cursor:pointer}
.fr-modal-btn.cancel{background:#f5f5f5;color:#666}
.fr-modal-btn.confirm{background:#fe2c55;color:#fff;flex:1}
.toast{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.75);color:#fff;padding:10px 20px;border-radius:6px;font-size:14px;z-index:9999;pointer-events:none}
</style>

<script>
function togglePlusMenu(e) {
    e.stopPropagation();
    const menu = document.getElementById('msgPlusMenu');
    menu.style.display = menu.style.display === 'none' ? '' : 'none';
}
document.addEventListener('click', function() {
    document.getElementById('msgPlusMenu').style.display = 'none';
});

// 全局加好友搜索（通过+按钮触发，搜索模式）
let frDirectTargetId = 0;

function showAddFriendGlobal() {
    document.getElementById('msgPlusMenu').style.display = 'none';
    document.getElementById('frSearchSection').style.display = '';
    document.getElementById('frDirectSection').style.display = 'none';
    document.getElementById('frDirectFooter').style.display = 'none';
    document.getElementById('frGlobalModal').style.display = 'flex';
    document.getElementById('frSearchInput').value = '';
    document.getElementById('frSearchResult').innerHTML = '';
    document.getElementById('frSearchInput').focus();
}
function showAddFriendDirect(uid, username) {
    frDirectTargetId = uid;
    document.getElementById('frSearchSection').style.display = 'none';
    document.getElementById('frDirectSection').style.display = '';
    document.getElementById('frDirectFooter').style.display = 'flex';
    document.getElementById('frTargetName').textContent = username;
    document.getElementById('frMessage').value = '';
    document.getElementById('frGlobalModal').style.display = 'flex';
}
function hideFRGlobal() {
    document.getElementById('frGlobalModal').style.display = 'none';
    document.getElementById('frSearchResult').innerHTML = '';
}
async function sendFriendRequestFromModal() {
    const msg = document.getElementById('frMessage').value.trim();
    const fd = new FormData();
    fd.append('action', 'send_friend_request');
    fd.append('user_id', frDirectTargetId);
    fd.append('message', msg);
    const res = await fetch('index.php?api=messages', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        hideFRGlobal();
        showToast(data.message || '已发送');
    } else {
        showToast(data.error || '发送失败');
    }
}
function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
}
document.getElementById('frSearchInput').addEventListener('input', async function() {
    const q = this.value.trim();
    if (q.length < 1) { document.getElementById('frSearchResult').innerHTML = ''; return; }
    const res = await fetch('index.php?api=search&action=query&q=' + encodeURIComponent(q) + '&type=users');
    const data = await res.json();
    const c = document.getElementById('frSearchResult');
    if (data.results && data.results.length) {
        c.innerHTML = data.results.map(u => {
            const av = u.avatar ? 'uploads/avatars/' + u.avatar : 'assets/images/default-avatar.svg';
            return `<div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f0f0f0;cursor:pointer" onclick="showAddFriendDirect(${u.id},'${u.username.replace(/'/g,"\\'")}')">
                <img src="${av}" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                <div style="flex:1"><div style="font-size:14px;color:#333">${u.username}</div><div style="font-size:11px;color:#999">${u.bio||''}</div></div>
            </div>`;
        }).join('');
    } else {
        c.innerHTML = '<p style="color:#999;font-size:13px;text-align:center;padding:10px">未找到用户</p>';
    }
});

async function loadMessages() {
    const list = document.getElementById('msgList');
    list.innerHTML = '<div class="msg-loading">加载中...</div>';
    try {
        const fd = new FormData();
        fd.append('action', 'list');
        const res = await fetch('index.php?api=messages', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.items && data.items.length) {
            // Separate system messages from others
            const sysMsgs = data.items.filter(m => m.item_type === 'system');
            const otherMsgs = data.items.filter(m => m.item_type !== 'system');

            let html = '';

            // System messages as expandable group
            if (sysMsgs.length > 0) {
                const unread = sysMsgs.filter(m => m.is_read == 0).length;
                const latest = sysMsgs[0]; // already sorted DESC
                html += renderSysGroup(latest, unread, sysMsgs);
            }

            // Other messages individually
            html += otherMsgs.map(renderItem).join('');
            list.innerHTML = html;
        } else {
            list.innerHTML = '<div class="msg-empty"><div class="msg-empty-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><p>暂无消息</p></div>';
        }
    } catch(e) {
        list.innerHTML = '<div class="msg-empty"><p>加载失败</p></div>';
    }
}

function renderSysGroup(latest, unread, allMsgs) {
    const time = (latest.created_at || '').replace('T', ' ').substring(0, 16);
    const badge = unread > 0 ? `<span class="sys-badge">${unread}</span>` : '';

    return `<div class="msg-item sys-group" onclick="location.href='index.php?page=system-messages'">
        <div class="msg-sys-icon">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ff2442" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </div>
        <div class="msg-content-wrapper">
            <div class="msg-header">
                <span class="msg-username">系统消息${badge}</span>
                <span class="msg-time">${time}</span>
            </div>
            <div class="msg-body sys-preview">${esc(latest.content)}</div>
        </div>
        <svg class="msg-enter-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
    </div>`;
}

function renderItem(m) {
    const time = (m.created_at || '').replace('T', ' ').substring(0, 16);

    if (m.item_type === 'friend_request') {
        const accepted = m.status === 'accepted';
        const rejected = m.status === 'rejected';
        const pending = m.status === 'pending';
        const avatar = m.from_avatar ? 'uploads/avatars/' + m.from_avatar : 'assets/images/default-avatar.svg';
        const onClick = accepted
            ? `onclick="location.href='index.php?page=chat&peer=${m.from_user_id}&name=${encodeURIComponent(m.from_username)}'" style="cursor:pointer"`
            : '';

        return `<div class="msg-item" ${onClick}>
            <img src="${avatar}" class="msg-avatar" alt="">
            <div class="msg-content-wrapper">
                <div class="msg-header">
                    <span class="msg-username">${m.from_username}</span>
                    <span class="msg-time">${time}</span>
                </div>
                <div class="msg-body">${esc(m.fr_message || '')}</div>
                <div class="msg-fr-footer">
                    ${pending ? `<button class="fr-accept-btn" onclick="event.stopPropagation();handleFR(${m.fr_id},'accept',this)">接受</button><button class="fr-reject-btn" onclick="event.stopPropagation();handleFR(${m.fr_id},'reject',this)">拒绝</button>` : ''}
                    ${accepted ? '<span class="fr-status accepted">已接受</span>' : ''}
                    ${rejected ? '<span class="fr-status rejected">已拒绝</span>' : ''}
                </div>
            </div>
        </div>`;
    }

    if (m.item_type === 'system') {
        return `<div class="msg-item ${m.is_read == 0 ? 'msg-unread' : ''}" onclick="markRead(${m.id})">
            <div class="msg-sys-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ff2442" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            </div>
            <div class="msg-content-wrapper">
                <div class="msg-header">
                    <span class="msg-username">小蓝书通知</span>
                    <span class="msg-time">${time}</span>
                </div>
                <div class="msg-body">${esc(m.content)}</div>
            </div>
            ${m.is_read == 0 ? '<div class="msg-dot"></div>' : ''}
        </div>`;
    }

    if (m.item_type === 'chat') {
        const avatar = m.peer_avatar ? 'uploads/avatars/' + m.peer_avatar : 'assets/images/default-avatar.svg';
        const peer = m.peer_id || m.from_user_id;
        const isFromMe = m.from_user_id == <?= currentUserId() ?>;
        const displayName = m.peer_username || '';
        const msgPrefix = isFromMe ? '' : '';
        return `<div class="msg-item ${m.is_read == 0 ? 'msg-unread' : ''}" onclick="location.href='index.php?page=chat&peer=${peer}&name=${encodeURIComponent(m.peer_username || '')}'">
            <img src="${avatar}" class="msg-avatar" alt="">
            <div class="msg-content-wrapper">
                <div class="msg-header">
                    <span class="msg-username">${displayName}</span>
                    <span class="msg-time">${time}</span>
                </div>
                <div class="msg-body">${msgPrefix}${esc(m.content)}</div>
            </div>
            ${m.is_read == 0 ? '<div class="msg-dot"></div>' : ''}
        </div>`;
    }

    if (m.item_type === 'group_chat') {
        const avatar = m.from_avatar ? 'uploads/avatars/' + m.from_avatar : 'assets/images/default-avatar.svg';
        return `<div class="msg-item" onclick="location.href='index.php?page=chat&group=${m.group_id}&name=${encodeURIComponent(m.group_name||'群聊')}'">
            <div class="msg-group-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#4a90d9" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </div>
            <div class="msg-content-wrapper">
                <div class="msg-header">
                    <span class="msg-username">${m.group_name || '群聊'}</span>
                    <span class="msg-time">${time}</span>
                </div>
                <div class="msg-body">${m.from_username}: ${esc(m.content)}</div>
            </div>
        </div>`;
    }

    return '';
}

function esc(s) { return (s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

async function markRead(id) {
    const fd = new FormData();
    fd.append('action', 'mark_read');
    fd.append('id', id);
    await fetch('index.php?api=messages', { method: 'POST', body: fd });
    document.querySelector(`[onclick="markRead(${id})"]`)?.classList.remove('msg-unread');
}

async function handleFR(frId, decision, btn) {
    const fd = new FormData();
    fd.append('action', 'handle_friend_request');
    fd.append('request_id', frId);
    fd.append('decision', decision);
    const res = await fetch('index.php?api=messages', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        // Reload the list
        await loadMessages();
    }
}

// Suggested users
async function loadSuggestedUsers() {
    const fd = new FormData();
    fd.append('action', 'suggested_users');
    try {
        const res = await fetch('index.php?api=messages', { method: 'POST', body: fd });
        const data = await res.json();
        const c = document.getElementById('suggestedUsersList');
        if (data.users && data.users.length) {
            c.innerHTML = data.users.map(u => {
                const av = u.avatar ? 'uploads/avatars/' + u.avatar : 'assets/images/default-avatar.svg';
                return `<div class="suggested-user-item">
                    <a href="index.php?page=profile&user=${u.id}" class="suggested-user-link">
                        <img src="${av}" class="suggested-user-avatar" alt="">
                        <div class="suggested-user-info">
                            <span class="suggested-user-name">${u.username}</span>
                            <span class="suggested-user-bio">${u.bio || ''}</span>
                            <span class="suggested-user-stats">${fmt(u.followers_count)} 粉丝 · ${u.notes_count} 笔记</span>
                        </div>
                    </a>
                    <button class="suggested-follow-btn" onclick="event.preventDefault();event.stopPropagation();showAddFriendDirect(${u.id},'${u.username.replace(/'/g,"\\\'")}')">加好友</button>
                </div>`;
            }).join('');
        } else {
            c.innerHTML = '<span class="loading-text">暂无推荐</span>';
        }
    } catch(e) {
        document.getElementById('suggestedUsersList').innerHTML = '<span class="loading-text">加载失败</span>';
    }
}

function fmt(n) { return n >= 10000 ? (n/10000).toFixed(1) + '万' : n; }

loadMessages();
loadSuggestedUsers();
</script>
<?php endif; ?>
