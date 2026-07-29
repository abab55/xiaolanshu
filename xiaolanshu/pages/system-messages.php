<?php
if (!isLoggedIn()) {
    header('Location: index.php?page=login');
    exit;
}
$userId = currentUserId();
$messages = db()->fetchAll(
    "SELECT * FROM messages WHERE to_user_id = :uid AND type = 'system' ORDER BY created_at DESC",
    [':uid' => $userId]
);
// Mark all as read
db()->query("UPDATE messages SET is_read = 1 WHERE to_user_id = :uid AND type = 'system' AND is_read = 0", [':uid' => $userId]);
?>

<div class="sys-msgs-page">
    <div class="sys-msgs-list">
        <?php if ($messages): ?>
            <?php foreach ($messages as $m): ?>
            <div class="sys-msg-item">
                <div class="sys-msg-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ff2442" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                </div>
                <div class="sys-msg-body">
                    <p class="sys-msg-text"><?= h($m['content']) ?></p>
                    <span class="sys-msg-time"><?= date('Y-m-d H:i', strtotime($m['created_at'])) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="msg-empty"><p>暂无系统消息</p></div>
        <?php endif; ?>
    </div>
</div>
