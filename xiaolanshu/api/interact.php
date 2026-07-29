<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'like':
        requireLogin();
        $noteId = (int)($_POST['note_id'] ?? 0);

        $existing = db()->fetch(
            "SELECT id FROM likes WHERE user_id = :uid AND note_id = :nid",
            [':uid' => currentUserId(), ':nid' => $noteId]
        );

        if ($existing) {
            db()->query("DELETE FROM likes WHERE id = :id", [':id' => $existing['id']]);
            db()->query("UPDATE notes SET likes_count = MAX(0, likes_count - 1) WHERE id = :id", [':id' => $noteId]);
            $note = db()->fetch("SELECT likes_count FROM notes WHERE id = :id", [':id' => $noteId]);
            jsonResponse(['liked' => false, 'count' => $note['likes_count']]);
        } else {
            db()->insert(
                "INSERT INTO likes (user_id, note_id) VALUES (:uid, :nid)",
                [':uid' => currentUserId(), ':nid' => $noteId]
            );
            db()->query("UPDATE notes SET likes_count = likes_count + 1 WHERE id = :id", [':id' => $noteId]);
            $note = db()->fetch("SELECT likes_count FROM notes WHERE id = :id", [':id' => $noteId]);
            jsonResponse(['liked' => true, 'count' => $note['likes_count']]);
        }
        break;

    case 'collect':
        requireLogin();
        $noteId = (int)($_POST['note_id'] ?? 0);

        $existing = db()->fetch(
            "SELECT id FROM collections WHERE user_id = :uid AND note_id = :nid",
            [':uid' => currentUserId(), ':nid' => $noteId]
        );

        if ($existing) {
            db()->query("DELETE FROM collections WHERE id = :id", [':id' => $existing['id']]);
            db()->query("UPDATE notes SET collects_count = MAX(0, collects_count - 1) WHERE id = :id", [':id' => $noteId]);
            db()->query("UPDATE users SET collects_count = MAX(0, collects_count - 1) WHERE id = :id", [':id' => currentUserId()]);
            $note = db()->fetch("SELECT collects_count FROM notes WHERE id = :id", [':id' => $noteId]);
            jsonResponse(['collected' => false, 'count' => $note['collects_count']]);
        } else {
            db()->insert(
                "INSERT INTO collections (user_id, note_id) VALUES (:uid, :nid)",
                [':uid' => currentUserId(), ':nid' => $noteId]
            );
            db()->query("UPDATE notes SET collects_count = collects_count + 1 WHERE id = :id", [':id' => $noteId]);
            db()->query("UPDATE users SET collects_count = collects_count + 1 WHERE id = :id", [':id' => currentUserId()]);
            $note = db()->fetch("SELECT collects_count FROM notes WHERE id = :id", [':id' => $noteId]);
            jsonResponse(['collected' => true, 'count' => $note['collects_count']]);
        }
        break;

    case 'comment':
        requireLogin();
        $noteId = (int)($_POST['note_id'] ?? 0);
        $content = trim($_POST['content'] ?? '');
        $parentId = (int)($_POST['parent_id'] ?? 0);

        if (empty($content)) jsonError('请输入评论内容');
        if (mb_strlen($content) > 500) jsonError('评论内容不能超过500字');

        $cid = db()->insert(
            "INSERT INTO comments (user_id, note_id, parent_id, content) VALUES (:uid, :nid, :pid, :c)",
            [':uid' => currentUserId(), ':nid' => $noteId, ':pid' => $parentId, ':c' => $content]
        );
        db()->query("UPDATE notes SET comments_count = comments_count + 1 WHERE id = :id", [':id' => $noteId]);

        $comment = db()->fetch(
            "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id WHERE c.id = :id",
            [':id' => $cid]
        );

        jsonResponse(['success' => true, 'comment' => $comment]);
        break;

    case 'delete_comment':
        requireLogin();
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $comment = db()->fetch("SELECT * FROM comments WHERE id = :id", [':id' => $commentId]);
        if (!$comment) jsonError('评论不存在');
        if ($comment['user_id'] != currentUserId()) jsonError('无权限');

        db()->query("DELETE FROM comments WHERE id = :id", [':id' => $commentId]);
        db()->query("UPDATE notes SET comments_count = MAX(0, comments_count - 1) WHERE id = :id", [':id' => $comment['note_id']]);

        jsonResponse(['success' => true]);
        break;
}
