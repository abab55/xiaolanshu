<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'toggle':
        requireLogin();
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === currentUserId()) jsonError('不能关注自己');

        $existing = db()->fetch(
            "SELECT id FROM follows WHERE follower_id = :fid AND following_id = :tid",
            [':fid' => currentUserId(), ':tid' => $userId]
        );

        if ($existing) {
            db()->query("DELETE FROM follows WHERE id = :id", [':id' => $existing['id']]);
            db()->query("UPDATE users SET following_count = MAX(0, following_count - 1) WHERE id = :id", [':id' => currentUserId()]);
            db()->query("UPDATE users SET followers_count = MAX(0, followers_count - 1) WHERE id = :id", [':id' => $userId]);
            jsonResponse(['following' => false]);
        } else {
            db()->insert(
                "INSERT INTO follows (follower_id, following_id) VALUES (:fid, :tid)",
                [':fid' => currentUserId(), ':tid' => $userId]
            );
            db()->query("UPDATE users SET following_count = following_count + 1 WHERE id = :id", [':id' => currentUserId()]);
            db()->query("UPDATE users SET followers_count = followers_count + 1 WHERE id = :id", [':id' => $userId]);
            jsonResponse(['following' => true]);
        }
        break;
}
