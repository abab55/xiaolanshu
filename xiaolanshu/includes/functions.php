<?php
session_start();

function db() {
    return Database::getInstance();
}

function isLoggedIn() {
    if (!isset($_SESSION['user_id'])) return false;
    $exists = db()->fetch('SELECT id FROM users WHERE id = :id AND status = 1', [':id' => $_SESSION['user_id']]);
    if (!$exists) {
        $_SESSION = [];
        session_destroy();
        return false;
    }
    return true;
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return db()->fetch('SELECT * FROM users WHERE id = :id', [':id' => currentUserId()]);
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonError($message, $code = 400) {
    jsonResponse(['error' => $message], $code);
}

function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_GET['api']))) {
            jsonError('请先登录', 401);
        }
        redirect('index.php?page=login');
    }
}

function isAdmin() {
    if (!isLoggedIn()) return false;
    $user = currentUser();
    return $user && $user['is_admin'] == 1;
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        if (isset($_GET['api'])) jsonError('需要管理员权限', 403);
        redirect('index.php');
    }
}

function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60) return '刚刚';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 604800) return floor($diff / 86400) . '天前';
    return date('Y-m-d', strtotime($timestamp));
}

function formatCount($num) {
    if ($num >= 10000) return round($num / 10000, 1) . '万';
    if ($num >= 1000) return round($num / 1000, 1) . 'k';
    return $num;
}

function generatePlaceholderImage($text, $width = 400, $height = 400) {
    $hash = md5($text);
    $r = hexdec(substr($hash, 0, 2));
    $g = hexdec(substr($hash, 2, 2));
    $b = hexdec(substr($hash, 4, 2));

    $img = imagecreatetruecolor($width, $height);
    $bg = imagecolorallocate($img, $r, $g, $b);
    imagefill($img, 0, 0, $bg);

    $textColor = imagecolorallocate($img, 255, 255, 255);
    $lines = mb_str_split($text, 6);
    $fontSize = 5;
    $y = $height / 2 - (count($lines) * 15) / 2;
    foreach ($lines as $line) {
        $x = $width / 2 - (mb_strlen($line) * imagefontwidth($fontSize)) / 2;
        imagestring($img, $fontSize, $x, $y, $line, $textColor);
        $y += 18;
    }

    return $img;
}

function getUserAvatar($userId) {
    $user = db()->fetch('SELECT avatar, username FROM users WHERE id = :id', [':id' => $userId]);
    if (!$user) return 'assets/images/default-avatar.svg';
    $avatarPath = 'uploads/avatars/' . $user['avatar'];
    if ($user['avatar'] && file_exists(__DIR__ . '/../' . $avatarPath)) {
        return $avatarPath;
    }
    return 'assets/images/default-avatar.svg';
}

function getNoteCover($note) {
    $images = json_decode($note['images'], true);
    $firstImage = $images[0] ?? null;
    if ($firstImage && file_exists(__DIR__ . '/../' . $firstImage)) {
        return $firstImage;
    }
    return 'api/placeholder.php?text=' . urlencode($note['title']) . '&w=400&h=' . rand(300, 500);
}

function isLiked($noteId) {
    if (!isLoggedIn()) return false;
    $row = db()->fetch('SELECT id FROM likes WHERE user_id = :uid AND note_id = :nid', [
        ':uid' => currentUserId(), ':nid' => $noteId
    ]);
    return $row !== false;
}

function isCollected($noteId) {
    if (!isLoggedIn()) return false;
    $row = db()->fetch('SELECT id FROM collections WHERE user_id = :uid AND note_id = :nid', [
        ':uid' => currentUserId(), ':nid' => $noteId
    ]);
    return $row !== false;
}

function isFollowing($userId) {
    if (!isLoggedIn()) return false;
    $row = db()->fetch('SELECT id FROM follows WHERE follower_id = :fid AND following_id = :tid', [
        ':fid' => currentUserId(), ':tid' => $userId
    ]);
    return $row !== false;
}

function renderNoteCard($note) {
    $user = db()->fetch('SELECT id, username, avatar FROM users WHERE id = :id', [':id' => $note['user_id']]);
    $images = json_decode($note['images'], true);
    $firstImage = $images[0] ?? null;
    $coverUrl = $firstImage && file_exists(__DIR__ . '/../' . $firstImage)
        ? $firstImage
        : 'uploads/notes/default_cover.png';
    $height = rand(160, 280);
    $liked = isLoggedIn() ? isLiked($note['id']) : false;
    ob_start();
?>
<div class="note-card" onclick="location.href='index.php?page=note&id=<?= $note['id'] ?>'">
    <div class="note-card-image" style="height:<?= $height ?>px;">
        <img src="<?= h($coverUrl) ?>" alt="<?= h($note['title']) ?>" loading="lazy">
        <span class="note-image-count">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="white"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <?= formatCount($note['likes_count']) ?>
        </span>
    </div>
    <div class="note-card-body">
        <h3 class="note-card-title"><?= h($note['title']) ?></h3>
        <div class="note-card-footer">
            <div class="note-card-author">
                <img src="<?= h(getUserAvatar($note['user_id'])) ?>" class="author-avatar-sm" alt="">
                <span><?= h($user['username']) ?></span>
            </div>
        </div>
    </div>
</div>
<?php
    return ob_get_clean();
}
