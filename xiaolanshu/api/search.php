<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'query':
        $q = trim($_GET['q'] ?? '');
        $type = $_GET['type'] ?? 'notes';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;

        if (empty($q)) jsonResponse(['results' => [], 'has_more' => false]);

        if ($type === 'users') {
            $results = db()->fetchAll(
                "SELECT id, username, avatar, bio, followers_count, notes_count FROM users
                 WHERE username LIKE :q OR bio LIKE :q2 ORDER BY followers_count DESC LIMIT :limit OFFSET :offset",
                [':q' => "%$q%", ':q2' => "%$q%", ':limit' => $limit, ':offset' => $offset]
            );
            jsonResponse(['results' => $results, 'has_more' => count($results) >= $limit]);
        } else {
            $sql = "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id
                    WHERE n.title LIKE :q OR n.content LIKE :q2 OR n.tags LIKE :q3
                    ORDER BY n.likes_count DESC, n.created_at DESC LIMIT :limit OFFSET :offset";
            $results = db()->fetchAll($sql, [':q' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%", ':limit' => $limit, ':offset' => $offset]);
            $html = '';
            foreach ($results as $note) {
                $html .= renderNoteCard($note);
            }
            jsonResponse(['html' => $html, 'has_more' => count($results) >= $limit]);
        }
        break;

    case 'hot_tags':
        $tags = db()->fetchAll(
            "SELECT tags FROM notes WHERE tags != '' ORDER BY likes_count DESC LIMIT 100"
        );
        $tagCounts = [];
        foreach ($tags as $t) {
            foreach (explode(',', $t['tags']) as $tag) {
                $tag = trim($tag);
                if ($tag) {
                    $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
                }
            }
        }
        arsort($tagCounts);
        jsonResponse(array_slice(array_keys($tagCounts), 0, 30));
        break;
}
