let XLS = window.XLS || {};

async function toggleLike(noteId) {
    if (!XLS.loggedIn) { window.location.href = 'index.php?page=login'; return; }
    try {
        const res = await fetch('index.php?api=interact&action=like', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'note_id=' + noteId
        });
        const data = await res.json();
        const btn = document.getElementById('likeBtn');
        const countEl = document.getElementById('likeCount');
        if (btn && countEl) {
            btn.classList.toggle('active', data.liked);
            const svg = btn.querySelector('svg');
            if (svg) {
                svg.setAttribute('fill', data.liked ? '#fe2c55' : 'none');
                svg.setAttribute('stroke', data.liked ? '#fe2c55' : '#666');
            }
            countEl.textContent = formatNum(data.count);
        }
    } catch(e) { console.error(e); }
}

async function toggleCollect(noteId) {
    if (!XLS.loggedIn) { window.location.href = 'index.php?page=login'; return; }
    try {
        const res = await fetch('index.php?api=interact&action=collect', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'note_id=' + noteId
        });
        const data = await res.json();
        const btn = document.getElementById('collectBtn');
        const countEl = document.getElementById('collectCount');
        if (btn && countEl) {
            btn.classList.toggle('active', data.collected);
            const svg = btn.querySelector('svg');
            if (svg) {
                svg.setAttribute('fill', data.collected ? '#ffc107' : 'none');
                svg.setAttribute('stroke', data.collected ? '#ffc107' : '#666');
            }
            countEl.textContent = formatNum(data.count);
        }
    } catch(e) { console.error(e); }
}

async function toggleFollow(userId) {
    if (!XLS.loggedIn) { window.location.href = 'index.php?page=login'; return; }
    try {
        const res = await fetch('index.php?api=follow&action=toggle', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'user_id=' + userId
        });
        const data = await res.json();
        const btns = document.querySelectorAll('[data-follow-user="' + userId + '"]');
        btns.forEach(function(b) {
            b.classList.toggle('following', data.following);
            b.textContent = data.following ? '已关注' : '+ 关注';
        });
    } catch(e) { console.error(e); }
}

async function submitComment(noteId) {
    if (!XLS.loggedIn) { window.location.href = 'index.php?page=login'; return; }
    var input = document.getElementById('commentInput');
    var content = input.value.trim();
    if (!content) return;

    try {
        var res = await fetch('index.php?api=interact&action=comment', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'note_id=' + noteId + '&content=' + encodeURIComponent(content)
        });
        var data = await res.json();
        if (data.success && data.comment) {
            var c = data.comment;
            var list = document.getElementById('commentsList');
            var avatarPath = c.avatar ? 'uploads/avatars/' + c.avatar : 'assets/images/default-avatar.svg';
            var div = document.createElement('div');
            div.className = 'comment-item';
            div.style.animation = 'slideUp 0.3s ease';
            div.innerHTML =
                '<img src="' + avatarPath + '" class="comment-avatar" alt="">' +
                '<div class="comment-body">' +
                '<div class="comment-header">' +
                '<a href="index.php?page=profile&user=' + c.user_id + '" class="comment-username">' + escapeHtml(c.username) + '</a>' +
                '<span class="comment-time">刚刚</span>' +
                '</div>' +
                '<div class="comment-content">' + escapeHtml(c.content) + '</div>' +
                '<button class="comment-delete-btn" onclick="deleteComment(' + c.id + ', this)">删除</button>' +
                '</div>';
            list.insertBefore(div, list.firstChild);
            input.value = '';

            var cc = document.querySelector('.action-btn:nth-child(3) span');
            if (cc) {
                cc.textContent = formatNum((parseInt(cc.textContent) || 0) + 1);
            }
        } else if (data.error) {
            showToast(data.error);
        }
    } catch(e) { console.error(e); }
}

async function deleteComment(commentId, btn) {
    if (!confirm('确定删除这条评论吗？')) return;
    try {
        await fetch('index.php?api=interact&action=delete_comment', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'comment_id=' + commentId
        });
        btn.closest('.comment-item').remove();
    } catch(e) { console.error(e); }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatNum(n) {
    if (n >= 10000) return (n / 10000).toFixed(1) + '万';
    if (n >= 1000) return (n / 1000).toFixed(1) + 'k';
    return String(n);
}

function showToast(msg) {
    var t = document.createElement('div');
    t.className = 'toast';
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function() { t.remove(); }, 2000);
}
