<?php
if (!isLoggedIn()) { header('Location: index.php?page=login'); exit; }
$peerId = (int)($_GET['peer'] ?? 0);
$groupId = (int)($_GET['group'] ?? 0);
$peerName = $_GET['name'] ?? '';
$GLOBALS['_chat_title'] = $peerName;

if ($peerId > 0) {
    $peer = db()->fetch("SELECT id, username, avatar FROM users WHERE id = :id", [':id' => $peerId]);
    if (!$peer) { echo '<div class="error-page"><h2>用户不存在</h2></div>'; return; }
    $peerName = $peer['username'];
    $isMutual = db()->fetch(
        "SELECT COUNT(*) as cnt FROM follows a JOIN follows b ON a.following_id = b.follower_id
         WHERE a.follower_id = :me AND a.following_id = :them
           AND b.follower_id = :them2 AND b.following_id = :me2",
        [':me' => currentUserId(), ':them' => $peerId, ':them2' => $peerId, ':me2' => currentUserId()]
    );
} elseif ($groupId > 0) {
    $group = db()->fetch("SELECT * FROM groups_chat WHERE id = :id", [':id' => $groupId]);
    if (!$group) { echo '<div class="error-page"><h2>群不存在</h2></div>'; return; }
    $peerName = $group['name'];
}
?>

<div class="chat-page">
    <div class="chat-messages" id="chatMessages">
        <div class="msg-loading">加载中...</div>
    </div>

    <div class="chat-input-bar">
        <input type="text" id="chatInput" placeholder="说点什么..." maxlength="1000"
            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();sendMsg()}">
        <button class="chat-send-btn" onclick="sendMsg()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#fff" stroke="#fff" stroke-width="1"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        </button>
    </div>
</div>

<!-- 群信息面板 -->
<?php if ($groupId > 0): ?>
<div class="group-info-overlay" id="groupInfoOverlay" style="display:none" onclick="if(event.target===this)hideGroupInfo()">
    <div class="group-info-panel" id="groupInfoPanel"></div>
</div>
<?php endif; ?>

<!-- 删除好友确认弹窗 -->
<?php if ($peerId > 0): ?>
<div class="fr-modal-overlay" id="deleteFriendModal" style="display:none" onclick="if(event.target===this)hideDeleteFriend()">
    <div class="fr-modal">
        <div class="fr-modal-header"><span>删除好友</span><button class="fr-modal-close" onclick="hideDeleteFriend()">&times;</button></div>
        <div class="fr-modal-body"><p>确定要删除好友「<?= h($peerName) ?>」吗？删除后将无法发送消息。</p></div>
        <div class="fr-modal-footer">
            <button class="fr-modal-btn cancel" onclick="hideDeleteFriend()">取消</button>
            <button class="fr-modal-btn confirm" onclick="doDeleteFriend()">确定删除</button>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.group-info-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);z-index:200;display:flex;justify-content:center;align-items:flex-end}
.group-info-panel{width:100%;max-height:70vh;background:#fff;border-radius:16px 16px 0 0;overflow-y:auto;padding:20px 16px;padding-bottom:env(safe-area-inset-bottom,20px)}
.gi-header{display:flex;align-items:center;gap:12px;margin-bottom:16px}
.gi-avatar{width:56px;height:56px;border-radius:14px;object-fit:cover;background:#f0f0f0;flex-shrink:0}
.gi-avatar-upload{position:relative;cursor:pointer}
.gi-avatar-upload::after{content:'换';position:absolute;bottom:0;right:0;background:rgba(0,0,0,0.5);color:#fff;font-size:10px;padding:2px 4px;border-radius:4px}
.gi-info{flex:1;min-width:0}
.gi-name{font-size:17px;font-weight:600;color:#333;margin-bottom:4px}
.gi-number{font-size:12px;color:#999}
.gi-actions{display:flex;gap:8px;margin:12px 0}
.gi-action-btn{flex:1;padding:8px 0;border-radius:8px;border:1px solid #e0e0e0;background:#fff;font-size:13px;color:#333;text-align:center;cursor:pointer}
.gi-action-btn.danger{color:#fe2c55;border-color:#fe2c55}
.gi-section-title{font-size:13px;color:#999;margin:16px 0 8px;padding-top:12px;border-top:1px solid #f2f2f2}
.gi-member{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:0.5px solid #f5f5f5}
.gi-member-avatar{width:40px;height:40px;border-radius:50%;object-fit:cover;background:#f0f0f0}
.gi-member-info{flex:1;min-width:0}
.gi-member-name{font-size:14px;color:#333;display:flex;align-items:center;gap:4px}
.gi-member-badge{font-size:10px;padding:1px 5px;border-radius:4px;font-weight:500}
.gi-member-badge.owner{background:#fe2c55;color:#fff}
.gi-member-badge.admin{background:#ffb84d;color:#fff}
.gi-member-actions{display:flex;gap:6px}
.gi-member-meta{font-size:11px;color:#aaa}
.gi-member-btn{font-size:11px;padding:3px 8px;border-radius:4px;border:1px solid #ddd;background:#fff;color:#666;cursor:pointer}
.gi-member-btn.danger{color:#fe2c55;border-color:#fe2c55}
.chat-menu-btn{background:none;border:none;width:36px;height:36px;display:flex;align-items:center;justify-content:center;cursor:pointer;border-radius:50%}
.chat-menu-btn:active{background:rgba(0,0,0,0.05)}
.msg-empty{text-align:center;padding:40px 20px;color:#999;font-size:14px}
.msg-empty svg{display:block;margin:0 auto 12px}
.toast{position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:rgba(0,0,0,0.75);color:#fff;padding:10px 20px;border-radius:6px;font-size:14px;z-index:9999;pointer-events:none}
</style>

<script>
const IS_GROUP = <?= $groupId > 0 ? 'true' : 'false' ?>;
const PEER_ID = <?= $peerId ?: 0 ?>;
const GROUP_ID = <?= $groupId ?: 0 ?>;
const CURRENT_USER_ID = <?= currentUserId() ?>;

async function loadMessages() {
    const c = document.getElementById('chatMessages');
    c.innerHTML = '<div class="msg-loading">加载中...</div>';
    try {
        const fd = new FormData();
        fd.append('action', IS_GROUP ? 'group_list' : 'private_list');
        if (IS_GROUP) fd.append('group_id', GROUP_ID);
        else fd.append('peer_id', PEER_ID);

        const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.messages && data.messages.length) {
            c.innerHTML = data.messages.map(m => {
                const isMine = m.from_user_id == CURRENT_USER_ID;
                const cls = isMine ? 'chat-msg mine' : 'chat-msg';
                const name = IS_GROUP && !isMine ? `<span class="chat-sender">${m.username}</span>` : '';
                return `<div class="${cls}">
                    ${name}
                    <div class="chat-bubble">${esc2(m.content)}</div>
                </div>`;
            }).join('');
        } else {
            c.innerHTML = '<div class="msg-empty"><p>暂无消息，开始聊天吧~</p></div>';
        }
        c.scrollTop = c.scrollHeight;
    } catch(e) {
        c.innerHTML = '<div class="msg-empty"><p>加载失败</p></div>';
    }
}

async function sendMsg() {
    const input = document.getElementById('chatInput');
    const content = input.value.trim();
    if (!content) return;
    input.value = '';
    const fd = new FormData();
    fd.append('action', 'send');
    fd.append('content', content);
    if (IS_GROUP) fd.append('group_id', GROUP_ID);
    else fd.append('peer_id', PEER_ID);
    await fetch('index.php?api=chat', { method: 'POST', body: fd });
    loadMessages();
}

function esc2(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function showToast(msg) {
    const t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 2000);
}

// --- Chat menu (private) ---
function toggleChatMenu(e) {
    e.stopPropagation();
    <?php if ($peerId > 0): ?>
    showDeleteFriend();
    <?php elseif ($groupId > 0): ?>
    loadGroupInfo();
    <?php endif; ?>
}

// --- Delete friend ---
function showDeleteFriend() {
    document.getElementById('deleteFriendModal').style.display = 'flex';
}
function hideDeleteFriend() {
    document.getElementById('deleteFriendModal').style.display = 'none';
}
async function doDeleteFriend() {
    const fd = new FormData();
    fd.append('action', 'delete_friend');
    fd.append('peer_id', PEER_ID);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        hideDeleteFriend();
        showToast('已删除好友');
        setTimeout(() => location.href = 'index.php?page=messages', 800);
    }
}

// --- Group info ---
let groupInfoData = null;

async function loadGroupInfo() {
    const panel = document.getElementById('groupInfoPanel');
    panel.innerHTML = '<div class="msg-loading" style="padding:40px">加载中...</div>';
    document.getElementById('groupInfoOverlay').style.display = 'flex';

    const fd = new FormData();
    fd.append('action', 'group_info');
    fd.append('group_id', GROUP_ID);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    groupInfoData = data;

    const g = data.group;
    const av = g.avatar ? g.avatar : 'assets/images/default-avatar.svg';
    const avatarHtml = (data.my_role === 'owner' || data.my_role === 'admin')
        ? `<label class="gi-avatar-upload" onclick="document.getElementById('groupAvatarInput').click()">
             <img src="${av}?t=${Date.now()}" class="gi-avatar">
           </label>
           <input type="file" id="groupAvatarInput" accept="image/*" style="display:none" onchange="uploadGroupAvatar()">`
        : `<img src="${av}" class="gi-avatar">`;

    const actionsHtml = [];
    if (data.my_role === 'owner') {
        actionsHtml.push('<button class="gi-action-btn" onclick="promptTransfer()">转让群主</button>');
    }
    if (data.my_role === 'owner' || data.my_role === 'admin') {
        actionsHtml.push('<button class="gi-action-btn danger" onclick="inviteToGroup()">邀请成员</button>');
    }
    actionsHtml.push('<button class="gi-action-btn danger" onclick="confirmLeaveGroup()">退出群聊</button>');

    const membersHtml = data.members.map(m => {
        const mAv = m.avatar ? 'uploads/avatars/' + m.avatar : 'assets/images/default-avatar.svg';
        let badge = '';
        if (m.role === 'owner') badge = '<span class="gi-member-badge owner">群主</span>';
        else if (m.role === 'admin') badge = '<span class="gi-member-badge admin">管理员</span>';

        let actions = '';
        if (data.my_role === 'owner' && m.id != CURRENT_USER_ID) {
            if (m.role === 'member') {
                actions += `<button class="gi-member-btn" onclick="setAdmin(${m.id})">设为管理</button>`;
            } else if (m.role === 'admin') {
                actions += `<button class="gi-member-btn" onclick="removeAdmin(${m.id})">取消管理</button>`;
            }
            actions += `<button class="gi-member-btn danger" onclick="kickMember(${m.id})">移除</button>`;
        } else if (data.my_role === 'admin' && m.role === 'member' && m.id != CURRENT_USER_ID) {
            actions += `<button class="gi-member-btn danger" onclick="kickMember(${m.id})">移除</button>`;
        }

        return `<div class="gi-member">
            <img src="${mAv}" class="gi-member-avatar">
            <div class="gi-member-info">
                <div class="gi-member-name">${m.username}${badge}</div>
                <div class="gi-member-meta">${m.bio||''}</div>
            </div>
            ${actions ? `<div class="gi-member-actions">${actions}</div>` : ''}
        </div>`;
    }).join('');

    panel.innerHTML = `
        <div class="gi-header">
            ${avatarHtml}
            <div class="gi-info">
                <div class="gi-name">${g.name}</div>
                <div class="gi-number">群号: ${g.group_number || g.id}</div>
            </div>
        </div>
        <div class="gi-actions">${actionsHtml.join('')}</div>
        <div class="gi-section-title">群成员 (${data.members.length})</div>
        ${membersHtml}
    `;
}

function hideGroupInfo() {
    document.getElementById('groupInfoOverlay').style.display = 'none';
}

async function uploadGroupAvatar() {
    const file = document.getElementById('groupAvatarInput').files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('action', 'update_group_avatar');
    fd.append('group_id', GROUP_ID);
    fd.append('avatar', file);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        loadGroupInfo();
    } else {
        showToast(data.error || '上传失败');
    }
}

async function setAdmin(uid) {
    const fd = new FormData();
    fd.append('action', 'set_admin');
    fd.append('group_id', GROUP_ID);
    fd.append('user_id', uid);
    await fetch('index.php?api=chat', { method: 'POST', body: fd });
    loadGroupInfo();
}

async function removeAdmin(uid) {
    const fd = new FormData();
    fd.append('action', 'remove_admin');
    fd.append('group_id', GROUP_ID);
    fd.append('user_id', uid);
    await fetch('index.php?api=chat', { method: 'POST', body: fd });
    loadGroupInfo();
}

async function kickMember(uid) {
    if (!confirm('确定要将该成员移出群聊？')) return;
    const fd = new FormData();
    fd.append('action', 'kick_member');
    fd.append('group_id', GROUP_ID);
    fd.append('user_id', uid);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        loadGroupInfo();
    } else {
        showToast(data.error || '操作失败');
    }
}

function confirmLeaveGroup() {
    if (!confirm('确定要退出该群聊吗？')) return;
    leaveGroup();
}

async function leaveGroup() {
    const fd = new FormData();
    fd.append('action', 'leave_group');
    fd.append('group_id', GROUP_ID);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        hideGroupInfo();
        showToast('已退出群聊');
        setTimeout(() => location.href = 'index.php?page=messages', 800);
    }
}

function promptTransfer() {
    const uid = prompt('请输入要转让的用户ID：');
    if (!uid) return;
    transferOwner(parseInt(uid));
}

async function transferOwner(uid) {
    const fd = new FormData();
    fd.append('action', 'transfer_owner');
    fd.append('group_id', GROUP_ID);
    fd.append('user_id', uid);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        loadGroupInfo();
    } else {
        showToast(data.error || '转让失败');
    }
}

async function inviteToGroup() {
    const uid = prompt('请输入要邀请的用户ID：');
    if (!uid) return;
    const fd = new FormData();
    fd.append('action', 'invite_member');
    fd.append('group_id', GROUP_ID);
    fd.append('user_id', uid);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        showToast('邀请成功');
        loadGroupInfo();
    } else {
        showToast(data.error || '邀请失败');
    }
}

loadMessages();
setInterval(loadMessages, 5000);
</script>
