<?php
if (!isLoggedIn()) { header('Location: index.php?page=login'); exit; }
?>

<div class="create-group-page">
    <div class="cg-form">
        <label class="cg-label">群名称</label>
        <input type="text" id="groupName" class="cg-input" placeholder="输入群名称" maxlength="30">

        <label class="cg-label">选择好友</label>
        <div class="cg-friends-list" id="friendsList">
            <span class="loading-text">加载中...</span>
        </div>
    </div>

    <div class="cg-footer">
        <button class="cg-create-btn" id="createBtn" onclick="createGroup()" disabled>创建群聊</button>
    </div>
</div>

<script>
const selected = new Set();

async function loadFriends() {
    const fd = new FormData();
    fd.append('action', 'friends');
    try {
        const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
        const data = await res.json();
        const c = document.getElementById('friendsList');
        if (data.friends && data.friends.length) {
            c.innerHTML = data.friends.map(f => {
                const av = f.avatar ? 'uploads/avatars/' + f.avatar : 'assets/images/default-avatar.svg';
                return `<div class="cg-friend-item" data-id="${f.id}" onclick="toggleSelect(${f.id}, this)">
                    <div class="cg-check">${selected.has(f.id) ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="#ff2442" stroke="#ff2442" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>' : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>'}</div>
                    <img src="${av}" class="cg-avatar" alt="">
                    <span class="cg-name">${f.username}</span>
                </div>`;
            }).join('');
            if (selected.size === 0) { selected.clear(); updateCreateBtn(); }
        } else {
            c.innerHTML = '<p style="color:#999;text-align:center;padding:20px">暂无好友，先去关注一些人吧~</p>';
        }
    } catch(e) {
        c.innerHTML = '<p style="color:#999;text-align:center;padding:20px">加载失败</p>';
    }
}

function toggleSelect(id, el) {
    if (selected.has(id)) { selected.delete(id); } else { selected.add(id); }
    const check = el.querySelector('.cg-check');
    check.innerHTML = selected.has(id)
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="#ff2442" stroke="#ff2442" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>';
    updateCreateBtn();
}

function updateCreateBtn() {
    const btn = document.getElementById('createBtn');
    const hasName = document.getElementById('groupName').value.trim().length > 0;
    btn.disabled = !hasName;
    btn.textContent = `创建群聊${selected.size > 0 ? ' (' + selected.size + '人)' : ''}`;
}

document.getElementById('groupName').addEventListener('input', updateCreateBtn);

async function createGroup() {
    const name = document.getElementById('groupName').value.trim();
    if (!name) return;
    const fd = new FormData();
    fd.append('action', 'create_group');
    fd.append('name', name);
    fd.append('member_ids', JSON.stringify([...selected]));
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        location.href = 'index.php?page=chat&group=' + data.group_id + '&name=' + encodeURIComponent(name);
    } else {
        alert(data.error || '创建失败');
    }
}

loadFriends();
</script>
