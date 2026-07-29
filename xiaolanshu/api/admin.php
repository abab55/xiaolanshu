<?php
requireAdmin();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'stats':
        $totalUsers = db()->fetch("SELECT COUNT(*) as cnt FROM users")['cnt'];
        $totalNotes = db()->fetch("SELECT COUNT(*) as cnt FROM notes")['cnt'];
        $totalComments = db()->fetch("SELECT COUNT(*) as cnt FROM comments")['cnt'];
        $totalLikes = db()->fetch("SELECT COUNT(*) as cnt FROM likes")['cnt'];
        $totalCollections = db()->fetch("SELECT COUNT(*) as cnt FROM collections")['cnt'];
        $todayNotes = db()->fetch("SELECT COUNT(*) as cnt FROM notes WHERE date(created_at) = date('now')")['cnt'];
        $todayUsers = db()->fetch("SELECT COUNT(*) as cnt FROM users WHERE date(created_at) = date('now')")['cnt'];
        $todayComments = db()->fetch("SELECT COUNT(*) as cnt FROM comments WHERE date(created_at) = date('now')")['cnt'];

        jsonResponse([
            'total_users' => $totalUsers,
            'total_notes' => $totalNotes,
            'total_comments' => $totalComments,
            'total_likes' => $totalLikes,
            'total_collections' => $totalCollections,
            'today_notes' => $todayNotes,
            'today_users' => $todayUsers,
            'today_comments' => $todayComments,
        ]);
        break;

    case 'users':
        $page = max(1, (int)($_GET['p'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM users";
        $countSql = "SELECT COUNT(*) as cnt FROM users";
        $params = [];
        $countParams = [];

        if ($search) {
            $where = " WHERE username LIKE :q OR email LIKE :q2";
            $sql .= $where;
            $countSql .= $where;
            $params[':q'] = "%$search%";
            $params[':q2'] = "%$search%";
            $countParams[':q'] = "%$search%";
            $countParams[':q2'] = "%$search%";
        }

        $total = db()->fetch($countSql, $countParams)['cnt'];
        $sql .= " ORDER BY id ASC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $users = db()->fetchAll($sql, $params);
        jsonResponse(['users' => $users, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
        break;

    case 'user_toggle_status':
        $userId = (int)($_POST['user_id'] ?? 0);
        $user = db()->fetch("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
        if (!$user) jsonError('用户不存在');
        $newStatus = $user['status'] ? 0 : 1;
        db()->query("UPDATE users SET status = :s WHERE id = :id", [':s' => $newStatus, ':id' => $userId]);
        jsonResponse(['success' => true, 'status' => $newStatus]);
        break;

    case 'user_delete':
        $userId = (int)($_POST['user_id'] ?? 0);
        $user = db()->fetch("SELECT * FROM users WHERE id = :id", [':id' => $userId]);
        if (!$user) jsonError('用户不存在');
        if ($user['is_admin']) jsonError('不能删除管理员');

        db()->query("DELETE FROM likes WHERE user_id = :id", [':id' => $userId]);
        db()->query("DELETE FROM collections WHERE user_id = :id", [':id' => $userId]);
        db()->query("DELETE FROM comments WHERE user_id = :id", [':id' => $userId]);
        db()->query("DELETE FROM follows WHERE follower_id = :id OR following_id = :id2", [':id' => $userId, ':id2' => $userId]);

        $notes = db()->fetchAll("SELECT id, images FROM notes WHERE user_id = :id", [':id' => $userId]);
        foreach ($notes as $n) {
            db()->query("DELETE FROM likes WHERE note_id = :id", [':id' => $n['id']]);
            db()->query("DELETE FROM collections WHERE note_id = :id", [':id' => $n['id']]);
            db()->query("DELETE FROM comments WHERE note_id = :id", [':id' => $n['id']]);
            $imgs = json_decode($n['images'], true);
            foreach ($imgs as $img) {
                $path = __DIR__ . '/../' . $img;
                if (file_exists($path)) unlink($path);
            }
        }
        db()->query("DELETE FROM notes WHERE user_id = :id", [':id' => $userId]);
        db()->query("DELETE FROM users WHERE id = :id", [':id' => $userId]);

        jsonResponse(['success' => true]);
        break;

    case 'notes':
        $page = max(1, (int)($_GET['p'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT n.*, u.username FROM notes n JOIN users u ON n.user_id = u.id";
        $countSql = "SELECT COUNT(*) as cnt FROM notes n";
        $params = [];
        $countParams = [];

        if ($search) {
            $where = " WHERE n.title LIKE :q OR n.content LIKE :q2";
            $sql .= $where;
            $countSql .= $where;
            $params[':q'] = "%$search%";
            $params[':q2'] = "%$search%";
            $countParams[':q'] = "%$search%";
            $countParams[':q2'] = "%$search%";
        }

        $total = db()->fetch($countSql, $countParams)['cnt'];
        $sql .= " ORDER BY n.id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $notes = db()->fetchAll($sql, $params);
        jsonResponse(['notes' => $notes, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
        break;

    case 'note_delete':
        $noteId = (int)($_POST['note_id'] ?? 0);
        $note = db()->fetch("SELECT * FROM notes WHERE id = :id", [':id' => $noteId]);
        if (!$note) jsonError('笔记不存在');

        $images = json_decode($note['images'], true);
        foreach ($images as $img) {
            $path = __DIR__ . '/../' . $img;
            if (file_exists($path)) unlink($path);
        }
        db()->query("DELETE FROM likes WHERE note_id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM collections WHERE note_id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM comments WHERE note_id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM notes WHERE id = :id", [':id' => $noteId]);
        db()->query("UPDATE users SET notes_count = MAX(0, notes_count - 1) WHERE id = :id", [':id' => $note['user_id']]);

        jsonResponse(['success' => true]);
        break;

    case 'note_approve':
        $noteId = (int)($_POST['note_id'] ?? 0);
        $status = ($_POST['status'] ?? 'approved') === 'rejected' ? 'rejected' : 'approved';
        $note = db()->fetch("SELECT * FROM notes WHERE id = :id", [':id' => $noteId]);
        if (!$note) jsonError('笔记不存在');
        db()->query("UPDATE notes SET status = :s WHERE id = :id", [':s' => $status, ':id' => $noteId]);
        jsonResponse(['success' => true, 'status' => $status]);
        break;

    case 'toggle_audit':
        $current = db()->fetch("SELECT value FROM settings WHERE key = 'audit_enabled'");
        $newValue = ($current && $current['value'] === '1') ? '0' : '1';
        db()->query("UPDATE settings SET value = :v WHERE key = 'audit_enabled'", [':v' => $newValue]);
        jsonResponse(['success' => true, 'enabled' => ($newValue === '1')]);
        break;

    case 'comments':
        $page = max(1, (int)($_GET['p'] ?? 1));
        $search = trim($_GET['q'] ?? '');
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT c.*, u.username as author_name, n.title as note_title, n.id as note_id
                FROM comments c JOIN users u ON c.user_id = u.id JOIN notes n ON c.note_id = n.id";
        $countSql = "SELECT COUNT(*) as cnt FROM comments c";
        $params = [];
        $countParams = [];

        if ($search) {
            $where = " WHERE c.content LIKE :q";
            $sql .= $where;
            $countSql .= $where;
            $params[':q'] = "%$search%";
            $countParams[':q'] = "%$search%";
        }

        $total = db()->fetch($countSql, $countParams)['cnt'];
        $sql .= " ORDER BY c.id DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $comments = db()->fetchAll($sql, $params);
        jsonResponse(['comments' => $comments, 'total' => $total, 'page' => $page, 'pages' => ceil($total / $limit)]);
        break;

    case 'comment_delete':
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $comment = db()->fetch("SELECT * FROM comments WHERE id = :id", [':id' => $commentId]);
        if (!$comment) jsonError('评论不存在');
        db()->query("DELETE FROM comments WHERE id = :id", [':id' => $commentId]);
        db()->query("UPDATE notes SET comments_count = MAX(0, comments_count - 1) WHERE id = :id", [':id' => $comment['note_id']]);
        jsonResponse(['success' => true]);
        break;
}
