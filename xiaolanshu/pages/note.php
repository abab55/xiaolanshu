<?php
$noteId = (int)($_GET['id'] ?? 0);
$note = db()->fetch(
    "SELECT n.*, u.username, u.avatar, u.bio as user_bio, u.notes_count as user_notes_count,
            u.followers_count as user_followers, u.following_count as user_following, u.id as author_id
     FROM notes n JOIN users u ON n.user_id = u.id WHERE n.id = :id",
    [':id' => $noteId]
);
if (!$note) {
    echo '<div class="error-page"><h2>笔记不存在</h2><a href="index.php">返回首页</a></div>';
    return;
}
db()->query("UPDATE notes SET views_count = views_count + 1 WHERE id = :id", [':id' => $noteId]);
$note['views_count']++;

$images = json_decode($note['images'], true);
$tags = $note['tags'] ? explode(',', $note['tags']) : [];
$liked = isLoggedIn() ? isLiked($noteId) : false;
$collected = isLoggedIn() ? isCollected($noteId) : false;
$following = isLoggedIn() ? isFollowing($note['author_id']) : false;
$comments = db()->fetchAll(
    "SELECT c.*, u.username, u.avatar FROM comments c JOIN users u ON c.user_id = u.id
     WHERE c.note_id = :nid ORDER BY c.created_at DESC",
    [':nid' => $noteId]
);
?>

<div class="note-page">\n    <div class="note-top-nav">
        <a href="index.php" class="note-back-btn">&#10094; 返回</a>
        <span class="note-top-title">笔记详情</span>
        <span></span>
    </div>
    <div class="note-image-carousel" id="imageCarousel">
        <div class="carousel-track" id="carouselTrack">
            <?php foreach ($images as $idx => $img): ?>
            <?php $imgUrl = file_exists(__DIR__ . '/../' . $img) ? $img : ('api/placeholder.php?text=' . urlencode($note['title']) . '&w=800'); ?>
            <div class="carousel-slide">
                <img src="<?= h($imgUrl) ?>" alt="" loading="<?= $idx === 0 ? 'eager' : 'lazy' ?>">
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="carousel-dots" id="carouselDots">
            <?php for ($i = 0; $i < count($images); $i++): ?>
            <span class="dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <button class="carousel-btn prev" onclick="slideCarousel(-1)">&#10094;</button>
        <button class="carousel-btn next" onclick="slideCarousel(1)">&#10095;</button>
        <span class="carousel-counter" id="carouselCounter">1/<?= count($images) ?></span>
    </div>

    <div class="note-detail-header">
        <div class="note-author-info">
            <a href="index.php?page=profile&user=<?= $note['author_id'] ?>" class="author-link">
                <img src="<?= h(getUserAvatar($note['author_id'])) ?>" class="author-avatar-lg" alt="">
                <div>
                    <h3 class="author-name"><?= h($note['username']) ?></h3>
                    <span class="author-stats"><?= formatCount($note['user_followers']) ?> 粉丝 · <?= $note['user_notes_count'] ?> 笔记</span>
                </div>
            </a>
            <?php if (isLoggedIn() && $note['author_id'] !== currentUserId()): ?>
            <button class="follow-btn <?= $following ? 'following' : '' ?>" data-follow-user="<?= $note['author_id'] ?>" onclick="toggleFollow(<?= $note['author_id'] ?>)">
                <?= $following ? '已关注' : '+ 关注' ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="note-detail-body">
        <h1 class="note-title"><?= h($note['title']) ?></h1>
        <div class="note-content"><?= nl2br(h($note['content'])) ?></div>
        <?php if ($tags): ?>
        <div class="note-tags">
            <?php foreach ($tags as $tag): $tag = trim($tag); if ($tag): ?>
            <a href="index.php?page=search&q=<?= urlencode($tag) ?>" class="note-tag">#<?= h($tag) ?></a>
            <?php endif; endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($note['location']): ?>
        <div class="note-location">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            <?= h($note['location']) ?>
        </div>
        <?php endif; ?>
        <div class="note-meta">
            <span><?= date('Y-m-d H:i', strtotime($note['created_at'])) ?></span>
            <span><?= formatCount($note['views_count']) ?> 浏览</span>
        </div>
    </div>

    <div class="note-actions-bar">
        <button class="action-btn <?= $liked ? 'active' : '' ?>" id="likeBtn" onclick="toggleLike(<?= $noteId ?>)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="<?= $liked ? '#ff2442' : 'none' ?>" stroke="<?= $liked ? '#ff2442' : '#666' ?>" stroke-width="2">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
            </svg>
            <span id="likeCount"><?= formatCount($note['likes_count']) ?></span>
        </button>
        <button class="action-btn <?= $collected ? 'active' : '' ?>" id="collectBtn" onclick="toggleCollect(<?= $noteId ?>)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="<?= $collected ? '#ffc107' : 'none' ?>" stroke="<?= $collected ? '#ffc107' : '#666' ?>" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
            <span id="collectCount"><?= formatCount($note['collects_count']) ?></span>
        </button>
        <button class="action-btn" onclick="document.getElementById('commentInput').focus()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
            </svg>
            <span><?= formatCount($note['comments_count']) ?></span>
        </button>
        <button class="action-btn" onclick="shareNote()">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#666" stroke-width="2">
                <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/>
                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
            </svg>
        </button>
    </div>

    <div class="note-comments-section">
        <h3>评论 (<?= $note['comments_count'] ?>)</h3>
        <?php if (isLoggedIn()): ?>
        <div class="comment-form">
            <img src="<?= h(getUserAvatar(currentUserId())) ?>" class="comment-avatar" alt="">
            <div class="comment-input-wrapper">
                <input type="text" id="commentInput" placeholder="说点什么..." maxlength="500" onkeydown="if(event.key==='Enter')submitComment(<?= $noteId ?>)">
                <button onclick="submitComment(<?= $noteId ?>)" class="comment-submit-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#1877F2" stroke="#1877F2" stroke-width="1"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </div>
        </div>
        <?php else: ?>
        <div class="comment-login-hint"><a href="index.php?page=login">登录</a>后发表评论</div>
        <?php endif; ?>

        <div class="comments-list" id="commentsList">
            <?php foreach ($comments as $c): ?>
            <div class="comment-item">
                <img src="<?= h(getUserAvatar($c['user_id'])) ?>" class="comment-avatar" alt="">
                <div class="comment-body">
                    <div class="comment-header">
                        <a href="index.php?page=profile&user=<?= $c['user_id'] ?>" class="comment-username"><?= h($c['username']) ?></a>
                        <span class="comment-time"><?= timeAgo($c['created_at']) ?></span>
                    </div>
                    <div class="comment-content"><?= h($c['content']) ?></div>
                    <?php if (isLoggedIn() && $c['user_id'] === currentUserId()): ?>
                    <button class="comment-delete-btn" onclick="deleteComment(<?= $c['id'] ?>, this)">删除</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php
    $category = $note['category'];
    $related = db()->fetchAll(
        "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id WHERE n.status = 'approved' AND n.id != :id AND n.category = :cat ORDER BY n.likes_count DESC LIMIT 6",
        [':id' => $noteId, ':cat' => $category]
    );
    if (!$related) {
        $related = db()->fetchAll(
            "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id WHERE n.status = 'approved' AND n.id != :id ORDER BY n.likes_count DESC LIMIT 6",
            [':id' => $noteId]
        );
    }
    if ($related): ?>
    <div class="related-notes">
        <h3>相关笔记</h3>
        <div class="waterfall-container">
            <?php foreach ($related as $r) echo renderNoteCard($r); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
let currentSlide = 0;
const totalSlides = <?= count($images) ?>;
const carouselTrack = document.getElementById('carouselTrack');

function slideCarousel(dir) {
    currentSlide = Math.max(0, Math.min(totalSlides - 1, currentSlide + dir));
    updateCarousel();
}

function updateCarousel() {
    carouselTrack.style.transform = `translateX(-${currentSlide * 100}%)`;
    document.querySelectorAll('#carouselDots .dot').forEach((d, i) => {
        d.classList.toggle('active', i === currentSlide);
    });
    document.getElementById('carouselCounter').textContent = `${currentSlide + 1}/${totalSlides}`;
}

document.querySelectorAll('#carouselDots .dot').forEach(dot => {
    dot.addEventListener('click', () => {
        currentSlide = parseInt(dot.dataset.index);
        updateCarousel();
    });
});

// Touch support
let touchStartX = 0;
carouselTrack.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; });
carouselTrack.addEventListener('touchend', e => {
    const diff = touchStartX - e.changedTouches[0].clientX;
    if (Math.abs(diff) > 50) slideCarousel(diff > 0 ? 1 : -1);
});

function shareNote() {
    if (navigator.share) {
        navigator.share({ title: '<?= h(addslashes($note['title'])) ?>', url: window.location.href });
    } else {
        navigator.clipboard.writeText(window.location.href).then(() => alert('链接已复制'));
    }
}
</script>
