<?php
if (!isLoggedIn()) jsonError('请先登录');

$action = $_POST['action'] ?? 'list';
$userId = currentUserId();

// --- Private chat: list messages ---
if ($action === 'private_list') {
    $peerId = (int)($_POST['peer_id'] ?? 0);
    if ($peerId <= 0) jsonError('参数错误');

    $msgs = db()->fetchAll(
        "SELECT m.*, u.username, u.avatar FROM messages m
         JOIN users u ON u.id = m.from_user_id
         WHERE m.group_id = 0
           AND ((m.from_user_id = :me AND m.to_user_id = :peer) OR (m.from_user_id = :peer2 AND m.to_user_id = :me2))
         ORDER BY m.created_at ASC LIMIT 100",
        [':me' => $userId, ':peer' => $peerId, ':peer2' => $peerId, ':me2' => $userId]
    );

    db()->query("UPDATE messages SET is_read = 1 WHERE to_user_id = :me AND from_user_id = :peer AND type = 'chat' AND is_read = 0",
        [':me' => $userId, ':peer' => $peerId]);

    jsonResponse(['messages' => $msgs]);
}

// --- Group chat: list messages ---
if ($action === 'group_list') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) jsonError('参数错误');

    $member = db()->fetch("SELECT 1 FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$member) jsonError('你不是群成员');

    $msgs = db()->fetchAll(
        "SELECT m.*, u.username, u.avatar FROM messages m
         JOIN users u ON u.id = m.from_user_id
         WHERE m.group_id = :g ORDER BY m.created_at ASC LIMIT 200",
        [':g' => $groupId]
    );

    jsonResponse(['messages' => $msgs]);
}

// --- Send message ---
if ($action === 'send') {
    $content = trim($_POST['content'] ?? '');
    $peerId = (int)($_POST['peer_id'] ?? 0);
    $groupId = (int)($_POST['group_id'] ?? 0);

    if (empty($content)) jsonError('消息不能为空');
    if (mb_strlen($content) > 1000) jsonError('消息过长');

    if ($peerId > 0) {
        $mutual = db()->fetch(
            "SELECT COUNT(*) as cnt FROM follows a JOIN follows b ON a.following_id = b.follower_id
             WHERE a.follower_id = :me AND a.following_id = :them
               AND b.follower_id = :them2 AND b.following_id = :me2",
            [':me' => $userId, ':them' => $peerId, ':them2' => $peerId, ':me2' => $userId]
        );
        if (!$mutual || $mutual['cnt'] == 0) jsonError('只能给好友发送消息');

        $id = db()->insert(
            "INSERT INTO messages (from_user_id, to_user_id, content, type, group_id) VALUES (:f, :t, :c, 'chat', 0)",
            [':f' => $userId, ':t' => $peerId, ':c' => $content]
        );
        jsonResponse(['success' => true, 'id' => $id]);
    }

    if ($groupId > 0) {
        $member = db()->fetch("SELECT 1 FROM group_members WHERE group_id = :g AND user_id = :u",
            [':g' => $groupId, ':u' => $userId]);
        if (!$member) jsonError('你不是群成员');

        $id = db()->insert(
            "INSERT INTO messages (from_user_id, to_user_id, content, type, group_id) VALUES (:f, 0, :c, 'group_chat', :g)",
            [':f' => $userId, ':c' => $content, ':g' => $groupId]
        );
        jsonResponse(['success' => true, 'id' => $id]);
    }

    jsonError('请指定接收方或群组');
}

// --- Friend list ---
if ($action === 'friends') {
    $friends = db()->fetchAll(
        "SELECT u.id, u.username, u.avatar, u.bio FROM users u
         JOIN follows a ON u.id = a.following_id
         JOIN follows b ON a.following_id = b.follower_id AND b.following_id = a.follower_id
         WHERE a.follower_id = :me
         ORDER BY u.username",
        [':me' => $userId]
    );
    jsonResponse(['friends' => $friends]);
}

// --- Delete friend ---
if ($action === 'delete_friend') {
    $peerId = (int)($_POST['peer_id'] ?? 0);
    if ($peerId <= 0) jsonError('参数错误');

    db()->query("DELETE FROM follows WHERE follower_id = :me AND following_id = :peer", [':me' => $userId, ':peer' => $peerId]);
    db()->query("DELETE FROM follows WHERE follower_id = :peer AND following_id = :me", [':peer' => $peerId, ':me' => $userId]);
    db()->query("UPDATE users SET followers_count = MAX(0, followers_count - 1) WHERE id = :id", [':id' => $peerId]);
    db()->query("UPDATE users SET following_count = MAX(0, following_count - 1) WHERE id = :id", [':id' => $peerId]);
    db()->query("UPDATE users SET followers_count = MAX(0, followers_count - 1) WHERE id = :id", [':id' => $userId]);
    db()->query("UPDATE users SET following_count = MAX(0, following_count - 1) WHERE id = :id", [':id' => $userId]);

    jsonResponse(['success' => true]);
}

// --- Create group ---
if ($action === 'create_group') {
    $name = trim($_POST['name'] ?? '');
    $memberIds = json_decode($_POST['member_ids'] ?? '[]', true);

    if (empty($name)) jsonError('请输入群名称');
    if (mb_strlen($name) > 30) jsonError('群名称过长');
    if (empty($memberIds) || !is_array($memberIds)) jsonError('请选择群成员');

    $groupNumber = str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
    $groupId = db()->insert(
        "INSERT INTO groups_chat (name, owner_id, group_number) VALUES (:n, :o, :gn)",
        [':n' => $name, ':o' => $userId, ':gn' => $groupNumber]
    );

    db()->query("INSERT INTO group_members (group_id, user_id, role) VALUES (:g, :u, 'owner')",
        [':g' => $groupId, ':u' => $userId]);

    foreach ($memberIds as $mid) {
        $mid = (int)$mid;
        if ($mid > 0 && $mid !== $userId) {
            db()->query("INSERT OR IGNORE INTO group_members (group_id, user_id, role) VALUES (:g, :u, 'member')",
                [':g' => $groupId, ':u' => $mid]);
            db()->query(
                "INSERT INTO messages (from_user_id, to_user_id, content, type) VALUES (0, :u, :c, 'system')",
                [':u' => $mid, ':c' => '你被邀请加入群聊「' . $name . '」']
            );
        }
    }

    db()->query(
        "INSERT INTO messages (from_user_id, to_user_id, content, type) VALUES (0, :u, :c, 'system')",
        [':u' => $userId, ':c' => '你已创建群聊「' . $name . '」']
    );

    jsonResponse(['success' => true, 'group_id' => $groupId]);
}

// --- My groups ---
if ($action === 'my_groups') {
    $groups = db()->fetchAll(
        "SELECT g.*, COUNT(gm2.user_id) as member_count
         FROM groups_chat g
         JOIN group_members gm ON g.id = gm.group_id AND gm.user_id = :me
         LEFT JOIN group_members gm2 ON g.id = gm2.group_id
         GROUP BY g.id
         ORDER BY g.created_at DESC",
        [':me' => $userId]
    );
    jsonResponse(['groups' => $groups]);
}

// --- Join group ---
if ($action === 'join_group') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) jsonError('参数错误');

    $group = db()->fetch("SELECT * FROM groups_chat WHERE id = :id", [':id' => $groupId]);
    if (!$group) jsonError('群不存在');

    db()->query("INSERT OR IGNORE INTO group_members (group_id, user_id, role) VALUES (:g, :u, 'member')",
        [':g' => $groupId, ':u' => $userId]);

    jsonResponse(['success' => true]);
}

// --- Leave group ---
if ($action === 'leave_group') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) jsonError('参数错误');

    $role = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$role) jsonError('你不是群成员');

    db()->query("DELETE FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);

    if ($role['role'] === 'owner') {
        $next = db()->fetch(
            "SELECT user_id FROM group_members WHERE group_id = :g ORDER BY CASE role WHEN 'admin' THEN 0 ELSE 1 END, rowid ASC LIMIT 1",
            [':g' => $groupId]
        );
        if ($next) {
            db()->query("UPDATE group_members SET role = 'owner' WHERE group_id = :g AND user_id = :u",
                [':g' => $groupId, ':u' => $next['user_id']]);
            db()->query("UPDATE groups_chat SET owner_id = :o WHERE id = :g",
                [':o' => $next['user_id'], ':g' => $groupId]);
        }
    }

    jsonResponse(['success' => true]);
}

// --- Group info ---
if ($action === 'group_info') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) jsonError('参数错误');

    $group = db()->fetch("SELECT * FROM groups_chat WHERE id = :id", [':id' => $groupId]);
    if (!$group) jsonError('群不存在');

    $members = db()->fetchAll(
        "SELECT u.id, u.username, u.avatar, u.bio, gm.role
         FROM group_members gm JOIN users u ON u.id = gm.user_id
         WHERE gm.group_id = :g ORDER BY CASE gm.role WHEN 'owner' THEN 0 WHEN 'admin' THEN 1 ELSE 2 END, u.username",
        [':g' => $groupId]
    );

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);

    jsonResponse(['group' => $group, 'members' => $members, 'my_role' => $myRole['role'] ?? '']);
}

// --- Set admin ---
if ($action === 'set_admin') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetId = (int)($_POST['user_id'] ?? 0);

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || $myRole['role'] !== 'owner') jsonError('只有群主可以设置管理员');

    $target = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    if (!$target) jsonError('该用户不是群成员');
    if ($target['role'] === 'owner') jsonError('不能修改群主角色');

    db()->query("UPDATE group_members SET role = 'admin' WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);

    jsonResponse(['success' => true]);
}

// --- Remove admin ---
if ($action === 'remove_admin') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetId = (int)($_POST['user_id'] ?? 0);

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || $myRole['role'] !== 'owner') jsonError('只有群主可以撤销管理员');

    $target = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    if (!$target || $target['role'] !== 'admin') jsonError('该用户不是管理员');

    db()->query("UPDATE group_members SET role = 'member' WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);

    jsonResponse(['success' => true]);
}

// --- Transfer ownership ---
if ($action === 'transfer_owner') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetId = (int)($_POST['user_id'] ?? 0);

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || $myRole['role'] !== 'owner') jsonError('只有群主可以转让群主');

    $target = db()->fetch("SELECT user_id FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    if (!$target) jsonError('该用户不是群成员');

    db()->query("UPDATE group_members SET role = 'admin' WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    db()->query("UPDATE group_members SET role = 'owner' WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    db()->query("UPDATE groups_chat SET owner_id = :o WHERE id = :g",
        [':o' => $targetId, ':g' => $groupId]);

    jsonResponse(['success' => true]);
}

// --- Update group avatar ---
if ($action === 'update_group_avatar') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    if ($groupId <= 0) jsonError('参数错误');

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || !in_array($myRole['role'], ['owner', 'admin'])) jsonError('只有群主或管理员可以修改群头像');

    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        jsonError('请选择图片');
    }

    $file = $_FILES['avatar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) jsonError('图片格式不支持');
    if ($file['size'] > 5 * 1024 * 1024) jsonError('图片不能超过5MB');

    $dir = __DIR__ . '/../uploads/groups';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $filename = 'group_' . $groupId . '_' . time() . '.' . $ext;
    move_uploaded_file($file['tmp_name'], $dir . '/' . $filename);

    db()->query("UPDATE groups_chat SET avatar = :a WHERE id = :id",
        [':a' => 'uploads/groups/' . $filename, ':id' => $groupId]);

    jsonResponse(['success' => true, 'avatar' => 'uploads/groups/' . $filename]);
}

// --- Kick member ---
if ($action === 'kick_member') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetId = (int)($_POST['user_id'] ?? 0);

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || !in_array($myRole['role'], ['owner', 'admin'])) jsonError('只有群主或管理员可以移除成员');

    $target = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    if (!$target) jsonError('该用户不是群成员');
    if ($target['role'] === 'owner') jsonError('不能移除群主');
    if ($myRole['role'] === 'admin' && $target['role'] === 'admin') jsonError('管理员不能移除其他管理员');

    db()->query("DELETE FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);

    jsonResponse(['success' => true]);
}

// --- Invite member ---
if ($action === 'invite_member') {
    $groupId = (int)($_POST['group_id'] ?? 0);
    $targetId = (int)($_POST['user_id'] ?? 0);

    $myRole = db()->fetch("SELECT role FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $userId]);
    if (!$myRole || !in_array($myRole['role'], ['owner', 'admin'])) jsonError('只有群主或管理员可以邀请成员');

    $user = db()->fetch("SELECT id FROM users WHERE id = :id", [':id' => $targetId]);
    if (!$user) jsonError('用户不存在');

    $existing = db()->fetch("SELECT 1 FROM group_members WHERE group_id = :g AND user_id = :u",
        [':g' => $groupId, ':u' => $targetId]);
    if ($existing) jsonError('该用户已在群中');

    db()->query("INSERT INTO group_members (group_id, user_id, role) VALUES (:g, :u, 'member')",
        [':g' => $groupId, ':u' => $targetId]);

    $groupName = db()->fetch("SELECT name FROM groups_chat WHERE id = :id", [':id' => $groupId])['name'] ?? '';
    db()->query(
        "INSERT INTO messages (from_user_id, to_user_id, content, type) VALUES (0, :u, :c, 'system')",
        [':u' => $targetId, ':c' => '你被邀请加入群聊「' . $groupName . '」']
    );

    jsonResponse(['success' => true]);
}

jsonError('未知操作');
