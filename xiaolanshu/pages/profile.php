<?php
$profileUserId = (int)($_GET['user'] ?? 0);
if (!$profileUserId && isLoggedIn()) $profileUserId = currentUserId();
$profileUser = db()->fetch("SELECT * FROM users WHERE id = :id", [':id' => $profileUserId]);
if (!$profileUser) {
    $hasSession = isset($_SESSION['user_id']);
    echo '<div class="error-page"><h2>用户不存在</h2>';
    if ($hasSession) {
        echo '<p style="color:#999;margin:8px 0">你的账号可能已被管理员删除</p>';
        echo '<a href="index.php?page=logout" class="profile-action-btn danger" style="display:inline-block;text-decoration:none;margin-top:12px">退出登录</a>';
    } else {
        echo '<a href="index.php" class="profile-action-btn" style="display:inline-block;text-decoration:none;margin-top:12px">返回首页</a>';
    }
    echo '</div>';
    return;
}
$isOwn = isLoggedIn() && currentUserId() === $profileUserId;
$isFollowing = isLoggedIn() ? isFollowing($profileUserId) : false;
$isMutual = false;
if (isLoggedIn() && !$isOwn) {
    $mutual = db()->fetch(
        "SELECT COUNT(*) as cnt FROM follows a JOIN follows b ON a.following_id = b.follower_id
         WHERE a.follower_id = :me AND a.following_id = :them
           AND b.follower_id = :them2 AND b.following_id = :me2",
        [':me' => currentUserId(), ':them' => $profileUserId, ':them2' => $profileUserId, ':me2' => currentUserId()]
    );
    $isMutual = $mutual && $mutual['cnt'] > 0;
}
?>

<div class="profile-page">
    <div class="profile-header-card">
        <div class="profile-avatar-section">
            <div class="profile-avatar-wrapper">
                <img src="<?= h(getUserAvatar($profileUserId)) ?>" class="profile-avatar" alt="">
            </div>
            <h2 class="profile-username"><?= h($profileUser['username']) ?></h2>
            <?php if ($profileUser['bio']): ?>
            <p class="profile-bio"><?= h($profileUser['bio']) ?></p>
            <?php endif; ?>
        </div>

        <div class="profile-stats">
            <div class="stat-item">
                <span class="stat-num"><?= $profileUser['notes_count'] ?></span>
                <span class="stat-label">笔记</span>
            </div>
            <a href="?page=profile&user=<?= $profileUserId ?>&tab=followers" class="stat-item">
                <span class="stat-num"><?= formatCount($profileUser['followers_count']) ?></span>
                <span class="stat-label">粉丝</span>
            </a>
            <a href="?page=profile&user=<?= $profileUserId ?>&tab=following" class="stat-item">
                <span class="stat-num"><?= formatCount($profileUser['following_count']) ?></span>
                <span class="stat-label">关注</span>
            </a>
            <div class="stat-item">
                <span class="stat-num"><?= $profileUser['collects_count'] ?? 0 ?></span>
                <span class="stat-label">收藏</span>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($isOwn): ?>
            <button class="profile-action-btn" onclick="showEditProfile()">编辑资料</button>
            <a href="index.php?page=logout" class="profile-action-btn secondary">退出登录</a>
            <?php else: ?>
            <button class="profile-action-btn <?= $isFollowing ? 'following' : '' ?>" data-follow-user="<?= $profileUserId ?>" onclick="toggleFollow(<?= $profileUserId ?>)">
                <?= $isFollowing ? '已关注' : '+ 关注' ?>
            </button>
            <button class="profile-action-btn secondary" onclick="showAddFriend(<?= $profileUserId ?>, '<?= h(addslashes($profileUser['username'])) ?>')">加好友</button>
            <?php if ($isMutual): ?>
            <button class="profile-action-btn danger" onclick="confirmDeleteFriend(<?= $profileUserId ?>, '<?= h(addslashes($profileUser['username'])) ?>')">删除好友</button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="profile-tabs">
        <button class="profile-tab active" onclick="switchProfileTab('notes', this)">笔记</button>
        <button class="profile-tab" onclick="switchProfileTab('collected', this)">收藏</button>
        <button class="profile-tab" onclick="switchProfileTab('liked', this)">赞过</button>
        <button class="profile-tab" onclick="switchProfileTab('commented', this)">评过</button>
    </div>

    <div class="waterfall-container" id="profileNotes">
        <?php
        $notes = db()->fetchAll(
            "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id WHERE n.status = 'approved' AND n.user_id = :uid ORDER BY n.created_at DESC LIMIT 20",
            [':uid' => $profileUserId]
        );
        foreach ($notes as $n) echo renderNoteCard($n);
        ?>
    </div>
    <div class="waterfall-container" id="profileCollected" style="display:none"></div>
    <div class="waterfall-container" id="profileLiked" style="display:none"></div>
    <div class="waterfall-container" id="profileCommented" style="display:none"></div>
</div>

<?php if ($isOwn): ?>
<div class="modal-overlay" id="editProfileModal" style="display:none">
    <div class="modal-card">
        <div class="modal-header">
            <h3>编辑资料</h3>
            <button class="modal-close" onclick="closeEditProfile()">&times;</button>
        </div>
        <form id="editProfileForm" onsubmit="submitEditProfile(event)">
            <div class="form-group">
                <label>头像</label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            <div class="form-group">
                <label>个人简介</label>
                <textarea name="bio" maxlength="200" placeholder="介绍一下自己..."><?= h($profileUser['bio']) ?></textarea>
            </div>
            <div class="form-group">
                <label>所在地</label>
                <input type="text" name="location" value="<?= h($profileUser['location']) ?>" placeholder="所在城市">
            </div>
            <div class="form-group">
                <label>性别</label>
                <select name="gender">
                    <option value="">不透露</option>
                    <option value="male" <?= $profileUser['gender']==='male'?'selected':'' ?>>男</option>
                    <option value="female" <?= $profileUser['gender']==='female'?'selected':'' ?>>女</option>
                </select>
            </div>
            <button type="submit" class="auth-btn">保存</button>
            <div class="auth-error" id="profileError" style="display:none"></div>
        </form>
    </div>
</div>

<script>
function showEditProfile() {
    document.getElementById('editProfileModal').style.display = 'flex';
}
function closeEditProfile() {
    document.getElementById('editProfileModal').style.display = 'none';
}

async function submitEditProfile(e) {
    e.preventDefault();
    var errorEl = document.getElementById('profileError');
    errorEl.style.display = 'none';
    var formData = new FormData(e.target);
    formData.append('action', 'update_profile');
    try {
        var res = await fetch('index.php?api=auth', { method: 'POST', body: formData });
        var data = await res.json();
        if (data.error) { errorEl.textContent = data.error; errorEl.style.display = 'block'; }
        else { location.reload(); }
    } catch(err) {
        errorEl.textContent = '网络错误';
        errorEl.style.display = 'block';
    }
}

var profileTabsLoaded = {collected: false, liked: false, commented: false};
var profileUserId = <?= $profileUserId ?>;

async function switchProfileTab(tab, btn) {
    document.querySelectorAll('.profile-tab').forEach(function(t){ t.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById('profileNotes').style.display = tab === 'notes' ? '' : 'none';
    document.getElementById('profileCollected').style.display = tab === 'collected' ? '' : 'none';
    document.getElementById('profileLiked').style.display = tab === 'liked' ? '' : 'none';
    document.getElementById('profileCommented').style.display = tab === 'commented' ? '' : 'none';

    if (tab === 'collected' && !profileTabsLoaded.collected) {
        profileTabsLoaded.collected = true;
        var container = document.getElementById('profileCollected');
        container.innerHTML = '<div class="no-results"><p>加载中...</p></div>';
        try {
            var r = await fetch('index.php?api=notes&action=collected&user_id=' + profileUserId);
            var d = await r.json();
            container.innerHTML = d.html || '<div class="no-results"><p>暂无收藏</p></div>';
        } catch(e) {
            container.innerHTML = '<div class="no-results"><p>加载失败</p></div>';
        }
    }

    if (tab === 'liked' && !profileTabsLoaded.liked) {
        profileTabsLoaded.liked = true;
        var container2 = document.getElementById('profileLiked');
        container2.innerHTML = '<div class="no-results"><p>加载中...</p></div>';
        try {
            var r2 = await fetch('index.php?api=notes&action=liked&user_id=' + profileUserId);
            var d2 = await r2.json();
            container2.innerHTML = d2.html || '<div class="no-results"><p>暂未点赞</p></div>';
        } catch(e) {
            container2.innerHTML = '<div class="no-results"><p>加载失败</p></div>';
        }
    }

    if (tab === 'commented' && !profileTabsLoaded.commented) {
        profileTabsLoaded.commented = true;
        var container3 = document.getElementById('profileCommented');
        container3.innerHTML = '<div class="no-results"><p>加载中...</p></div>';
        try {
            var r3 = await fetch('index.php?api=notes&action=commented&user_id=' + profileUserId);
            var d3 = await r3.json();
            container3.innerHTML = d3.html || '<div class="no-results"><p>暂未评论</p></div>';
        } catch(e) {
            container3.innerHTML = '<div class="no-results"><p>加载失败</p></div>';
        }
    }
}
</script>

<!-- 加好友弹窗 -->
<div class="fr-modal-overlay" id="frModal" style="display:none" onclick="if(event.target===this)hideAddFriend()">
    <div class="fr-modal">
        <div class="fr-modal-header">
            <span>添加好友</span>
            <button class="fr-modal-close" onclick="hideAddFriend()">&times;</button>
        </div>
        <div class="fr-modal-body">
            <p class="fr-modal-desc">发送好友申请给 <strong id="frTargetName"></strong></p>
            <textarea id="frMessage" class="fr-modal-input" placeholder="写个验证消息吧..." maxlength="200" rows="3"></textarea>
        </div>
        <div class="fr-modal-footer">
            <button class="fr-modal-btn cancel" onclick="hideAddFriend()">取消</button>
            <button class="fr-modal-btn confirm" onclick="sendFriendRequest()">发送</button>
        </div>
    </div>
</div>

<script>
let frTargetId = 0;

function showAddFriend(userId, username) {
    frTargetId = userId;
    document.getElementById('frTargetName').textContent = username;
    document.getElementById('frMessage').value = '';
    document.getElementById('frModal').style.display = 'flex';
}

function hideAddFriend() {
    document.getElementById('frModal').style.display = 'none';
}

async function sendFriendRequest() {
    const msg = document.getElementById('frMessage').value.trim();
    const fd = new FormData();
    fd.append('action', 'send_friend_request');
    fd.append('user_id', frTargetId);
    fd.append('message', msg);
    const res = await fetch('index.php?api=messages', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        hideAddFriend();
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

async function confirmDeleteFriend(uid, uname) {
    if (!confirm('确定要删除好友「' + uname + '」吗？删除后将无法互发消息。')) return;
    const fd = new FormData();
    fd.append('action', 'delete_friend');
    fd.append('peer_id', uid);
    const res = await fetch('index.php?api=chat', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        showToast('已删除好友');
        setTimeout(() => location.reload(), 800);
    } else {
        showToast(data.error || '删除失败');
    }
}
</script>
<?php endif; ?>
