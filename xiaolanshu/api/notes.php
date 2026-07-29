<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        requireLogin();
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $tags = trim($_POST['tags'] ?? '');
        $category = trim($_POST['category'] ?? 'other');
        $location = trim($_POST['location'] ?? '');

        if (empty($title)) jsonError('请输入标题');
        if (empty($content)) jsonError('请输入内容');

        // Handle image uploads
        $imagePaths = [];
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $count = is_array($files['name']) ? count($files['name']) : 1;
            $uploadDir = __DIR__ . '/../uploads/notes';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            for ($i = 0; $i < $count; $i++) {
                $name = is_array($files['name']) ? $files['name'][$i] : $files['name'];
                $tmp = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
                $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];

                if ($error !== UPLOAD_ERR_OK) continue;

                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $ext = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? strtolower($ext) : 'jpg';
                $filename = 'note_' . time() . '_' . $i . '.' . $ext;
                move_uploaded_file($tmp, "$uploadDir/$filename");
                $imagePaths[] = "uploads/notes/$filename";
            }
        }
        if (empty($imagePaths)) {
            $imagePaths[] = 'uploads/notes/default_cover.png';
        }

        $nid = db()->insert(
            "INSERT INTO notes (user_id, title, content, images, tags, category, location) VALUES (:uid, :t, :c, :imgs, :tags, :cat, :loc)",
            [
                ':uid' => currentUserId(),
                ':t' => $title,
                ':c' => $content,
                ':imgs' => json_encode($imagePaths, JSON_UNESCAPED_UNICODE),
                ':tags' => $tags,
                ':cat' => $category,
                ':loc' => $location,
            ]
        );

        db()->query("UPDATE users SET notes_count = notes_count + 1 WHERE id = :id", [':id' => currentUserId()]);

        jsonResponse(['success' => true, 'note_id' => $nid, 'message' => '发布成功']);
        break;

    case 'delete':
        requireLogin();
        $noteId = (int)($_POST['note_id'] ?? 0);
        $note = db()->fetch("SELECT * FROM notes WHERE id = :id", [':id' => $noteId]);
        if (!$note) jsonError('笔记不存在');
        if ($note['user_id'] != currentUserId()) jsonError('无权限');

        // Delete images
        $images = json_decode($note['images'], true);
        foreach ($images as $img) {
            $path = __DIR__ . '/../' . $img;
            if (file_exists($path)) unlink($path);
        }

        db()->query("DELETE FROM notes WHERE id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM likes WHERE note_id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM collections WHERE note_id = :id", [':id' => $noteId]);
        db()->query("DELETE FROM comments WHERE note_id = :id", [':id' => $noteId]);
        db()->query("UPDATE users SET notes_count = MAX(0, notes_count - 1) WHERE id = :id", [':id' => currentUserId()]);

        jsonResponse(['success' => true, 'message' => '已删除']);
        break;

    case 'detail':
        $noteId = (int)($_GET['note_id'] ?? 0);
        $note = db()->fetch(
            "SELECT n.*, u.username, u.avatar, u.bio as user_bio, u.notes_count as user_notes_count,
                    u.followers_count as user_followers, u.following_count as user_following
             FROM notes n JOIN users u ON n.user_id = u.id WHERE n.id = :id",
            [':id' => $noteId]
        );
        if (!$note) jsonError('笔记不存在');

        db()->query("UPDATE notes SET views_count = views_count + 1 WHERE id = :id", [':id' => $noteId]);

        $comments = db()->fetchAll(
            "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id
             WHERE c.note_id = :nid ORDER BY c.created_at DESC LIMIT 50",
            [':nid' => $noteId]
        );

        jsonResponse([
            'note' => $note,
            'comments' => $comments,
            'is_liked' => isLiked($noteId),
            'is_collected' => isCollected($noteId),
            'is_following' => isFollowing($note['user_id']),
        ]);
        break;

    case 'feed':
        $page = max(1, (int)($_GET['page'] ?? 1));
        $category = $_GET['category'] ?? '';
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id";
        $params = [];

        if ($category) {
            $sql .= " WHERE n.category = :cat";
            $params[':cat'] = $category;
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;

        $notes = db()->fetchAll($sql, $params);

        $html = '';
        foreach ($notes as $note) {
            $html .= renderNoteCard($note);
        }

        jsonResponse(['html' => $html, 'has_more' => count($notes) >= $limit]);
        break;

    case 'collected':
        requireLogin();
        $userId = (int)($_GET['user_id'] ?? currentUserId());
        $notes = db()->fetchAll(
            "SELECT n.*, u.username, u.avatar FROM notes n
             JOIN users u ON n.user_id = u.id
             JOIN collections c ON c.note_id = n.id
             WHERE c.user_id = :uid
             ORDER BY c.created_at DESC LIMIT 20",
            [':uid' => $userId]
        );
        $html = '';
        foreach ($notes as $note) {
            $html .= renderNoteCard($note);
        }
        jsonResponse(['html' => $html]);
        break;

    case 'liked':
        requireLogin();
        $userId = (int)($_GET['user_id'] ?? currentUserId());
        $notes = db()->fetchAll(
            "SELECT n.*, u.username, u.avatar FROM notes n
             JOIN users u ON n.user_id = u.id
             JOIN likes l ON l.note_id = n.id
             WHERE l.user_id = :uid
             ORDER BY l.created_at DESC LIMIT 20",
            [':uid' => $userId]
        );
        $html = '';
        foreach ($notes as $note) {
            $html .= renderNoteCard($note);
        }
        jsonResponse(['html' => $html]);
        break;

    case 'commented':
        requireLogin();
        $userId = (int)($_GET['user_id'] ?? currentUserId());
        $notes = db()->fetchAll(
            "SELECT DISTINCT n.*, u.username, u.avatar FROM notes n
             JOIN users u ON n.user_id = u.id
             JOIN comments c ON c.note_id = n.id
             WHERE c.user_id = :uid
             ORDER BY c.created_at DESC LIMIT 20",
            [':uid' => $userId]
        );
        $html = '';
        foreach ($notes as $note) {
            $html .= renderNoteCard($note);
        }
        jsonResponse(['html' => $html]);
        break;
}
