<?php
if (!isLoggedIn()) jsonError('请先登录');

$action = $_POST['action'] ?? 'list';
$userId = currentUserId();

if ($action === 'list') {
    // Unified list: system + chat + friend requests
    $items = [];

    // System messages
    $sys = db()->fetchAll(
        "SELECT id, from_user_id, to_user_id, content, type, is_read, created_at,
                NULL as status, NULL as fr_id, NULL as fr_message, NULL as from_username, NULL as from_avatar
         FROM messages WHERE to_user_id = :uid AND type = 'system'
         ORDER BY created_at DESC LIMIT 30",
        [':uid' => $userId]
    );
    foreach ($sys as $s) {
        $s['item_type'] = 'system';
        $items[] = $s;
    }

    // Chat messages — group by conversation, show latest per peer
    // Filter: only conversations with mutual follows (friends) remain after delete
    $chatRaw = db()->fetchAll(
        "SELECT m.id, m.from_user_id, m.to_user_id, m.content, m.type, m.is_read, m.created_at,
                p.username as peer_username, p.avatar as peer_avatar,
                CASE WHEN m.from_user_id = :uid THEN m.to_user_id ELSE m.from_user_id END as peer_id,
                NULL as status, NULL as fr_id, NULL as fr_message
         FROM messages m
         LEFT JOIN users p ON p.id = CASE WHEN m.from_user_id = :uid THEN m.to_user_id ELSE m.from_user_id END
         JOIN (
             SELECT MAX(id) as max_id
             FROM messages
             WHERE type = 'chat' AND (to_user_id = :uid2 OR from_user_id = :uid3)
             GROUP BY CASE WHEN from_user_id < to_user_id THEN from_user_id || '_' || to_user_id
                          ELSE to_user_id || '_' || from_user_id END
         ) latest ON m.id = latest.max_id
         WHERE m.type = 'chat' AND (m.to_user_id = :uid4 OR m.from_user_id = :uid5)
         ORDER BY m.created_at DESC",
        [':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId]
    );
    // Filter in PHP: only keep conversations where peer is still a mutual friend
    $chat = [];
    foreach ($chatRaw as $c) {
        $peerId = $c['peer_id'];
        $mutual = db()->fetch(
            "SELECT COUNT(*) as cnt FROM follows a JOIN follows b ON a.following_id = b.follower_id
             WHERE a.follower_id = :me AND a.following_id = :peer
               AND b.follower_id = :peer AND b.following_id = :me",
            [':me' => $userId, ':peer' => $peerId]
        );
        if ($mutual && $mutual['cnt'] > 0) {
            $chat[] = $c;
        }
    }
    foreach ($chat as $c) {
        $c['item_type'] = 'chat';
        $items[] = $c;
    }

    // Friend requests
    $frs = db()->fetchAll(
        "SELECT fr.id as fr_id, fr.from_user_id, fr.to_user_id, fr.message as fr_message, fr.status, fr.created_at,
                u.username as from_username, u.avatar as from_avatar,
                NULL as id, NULL as content, NULL as type, NULL as is_read
         FROM friend_requests fr JOIN users u ON u.id = fr.from_user_id
         WHERE fr.to_user_id = :uid
         ORDER BY fr.created_at DESC LIMIT 30",
        [':uid' => $userId]
    );
    foreach ($frs as $fr) {
        $fr['item_type'] = 'friend_request';
        $items[] = $fr;
    }

    // Group chat messages (latest per group for current user)
    $groups = db()->fetchAll(
        "SELECT DISTINCT m.group_id, g.name as group_name
         FROM messages m
         JOIN groups_chat g ON g.id = m.group_id
         WHERE m.type = 'group_chat'
           AND m.group_id IN (SELECT group_id FROM group_members WHERE user_id = :uid)
         ORDER BY m.created_at DESC",
        [':uid' => $userId]
    );
    foreach ($groups as $g) {
        $latest = db()->fetch(
            "SELECT m.*, u.username as from_username, u.avatar as from_avatar
             FROM messages m JOIN users u ON u.id = m.from_user_id
             WHERE m.group_id = :g AND m.type = 'group_chat'
             ORDER BY m.created_at DESC LIMIT 1",
            [':g' => $g['group_id']]
        );
        if ($latest) {
            $latest['item_type'] = 'group_chat';
            $latest['group_name'] = $g['group_name'];
            $items[] = $latest;
        }
    }

    // Sort by created_at DESC
    usort($items, function($a, $b) {
        return strcmp($b['created_at'], $a['created_at']);
    });

    jsonResponse(['items' => $items]);
}

if ($action === 'send_friend_request') {
    $toUserId = (int)($_POST['user_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($toUserId <= 0) jsonError('参数错误');
    if ($toUserId === $userId) jsonError('不能给自己发送好友请求');
    if (mb_strlen($message) > 200) jsonError('验证消息不能超过200字');

    $user = db()->fetch("SELECT id FROM users WHERE id = :id", [':id' => $toUserId]);
    if (!$user) jsonError('用户不存在');

    // Check if already friends (mutual follows)
    $mutual = db()->fetch(
        "SELECT COUNT(*) as cnt FROM follows a JOIN follows b ON a.following_id = b.follower_id
         WHERE a.follower_id = :me AND a.following_id = :them
           AND b.follower_id = :them2 AND b.following_id = :me2",
        [':me' => $userId, ':them' => $toUserId, ':them2' => $toUserId, ':me2' => $userId]
    );
    if ($mutual && $mutual['cnt'] > 0) jsonError('你们已经是好友了');

    // Check existing pending request
    $existing = db()->fetch(
        "SELECT COUNT(*) as cnt FROM friend_requests
         WHERE from_user_id = :me AND to_user_id = :them AND status = 'pending'",
        [':me' => $userId, ':them' => $toUserId]
    );
    if ($existing && $existing['cnt'] > 0) jsonError('你已发送过好友请求，请等待对方处理');

    db()->query(
        "INSERT INTO friend_requests (from_user_id, to_user_id, message, status) VALUES (:f, :t, :m, 'pending')",
        [':f' => $userId, ':t' => $toUserId, ':m' => $message]
    );

    jsonResponse(['success' => true, 'message' => '好友请求已发送']);
}

if ($action === 'handle_friend_request') {
    $frId = (int)($_POST['request_id'] ?? 0);
    $decision = $_POST['decision'] ?? ''; // 'accept' or 'reject'

    if (!in_array($decision, ['accept', 'reject'])) jsonError('参数错误');

    $fr = db()->fetch("SELECT * FROM friend_requests WHERE id = :id AND to_user_id = :uid AND status = 'pending'",
        [':id' => $frId, ':uid' => $userId]);
    if (!$fr) jsonError('请求不存在或已处理');

    if ($decision === 'accept') {
        // Create mutual follows
        db()->query("INSERT OR IGNORE INTO follows (follower_id, following_id) VALUES (:f, :t)",
            [':f' => $fr['from_user_id'], ':t' => $fr['to_user_id']]);
        db()->query("INSERT OR IGNORE INTO follows (follower_id, following_id) VALUES (:f, :t)",
            [':f' => $fr['to_user_id'], ':t' => $fr['from_user_id']]);

        // Update follower counts
        db()->query("UPDATE users SET followers_count = followers_count + 1, following_count = following_count + 1 WHERE id = :id",
            [':id' => $fr['from_user_id']]);
        db()->query("UPDATE users SET followers_count = followers_count + 1, following_count = following_count + 1 WHERE id = :id",
            [':id' => $fr['to_user_id']]);

        // Send a chat message as notification
        db()->query("INSERT INTO messages (from_user_id, to_user_id, content, type, is_read) VALUES (:f, :t, '我们已经是好友啦！开始聊天吧~', 'chat', 0)",
            [':f' => $fr['to_user_id'], ':t' => $fr['from_user_id']]);
    }

    db()->query(
        "UPDATE friend_requests SET status = :s, updated_at = datetime('now') WHERE id = :id",
        [':s' => $decision . 'ed', ':id' => $frId]
    );

    jsonResponse(['success' => true, 'message' => $decision === 'accept' ? '已添加好友' : '已拒绝']);
}

if ($action === 'mark_read') {
    $msgId = (int)($_POST['id'] ?? 0);
    if ($msgId <= 0) jsonError('参数错误');
    db()->query("UPDATE messages SET is_read = 1 WHERE id = :id AND to_user_id = :uid",
        [':id' => $msgId, ':uid' => $userId]);
    jsonResponse(['success' => true]);
}

if ($action === 'suggested_users') {
    $users = db()->fetchAll(
        "SELECT u.id, u.username, u.avatar, u.bio, u.notes_count, u.followers_count
         FROM users u
         WHERE u.id != :uid
           AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = :uid2)
         ORDER BY u.followers_count DESC LIMIT 10",
        [':uid' => $userId, ':uid2' => $userId]
    );
    jsonResponse(['users' => $users]);
}

jsonError('未知操作');
