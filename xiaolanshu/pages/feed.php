<div class="feed-header">
    <div class="category-tabs">
        <a href="index.php" class="category-tab <?= empty($_GET['category']) ? 'active' : '' ?>">推荐</a>
        <a href="?category=food" class="category-tab <?= ($_GET['category']??'') === 'food' ? 'active' : '' ?>">美食</a>
        <a href="?category=fashion" class="category-tab <?= ($_GET['category']??'') === 'fashion' ? 'active' : '' ?>">穿搭</a>
        <a href="?category=travel" class="category-tab <?= ($_GET['category']??'') === 'travel' ? 'active' : '' ?>">旅行</a>
        <a href="?category=beauty" class="category-tab <?= ($_GET['category']??'') === 'beauty' ? 'active' : '' ?>">美妆</a>
        <a href="?category=fitness" class="category-tab <?= ($_GET['category']??'') === 'fitness' ? 'active' : '' ?>">健身</a>
        <a href="?category=pet" class="category-tab <?= ($_GET['category']??'') === 'pet' ? 'active' : '' ?>">萌宠</a>
        <a href="?category=home" class="category-tab <?= ($_GET['category']??'') === 'home' ? 'active' : '' ?>">家居</a>
        <a href="?category=tech" class="category-tab <?= ($_GET['category']??'') === 'tech' ? 'active' : '' ?>">数码</a>
        <a href="?category=lifestyle" class="category-tab <?= ($_GET['category']??'') === 'lifestyle' ? 'active' : '' ?>">生活</a>
    </div>
</div>

<div class="waterfall-container" id="feed-container">
    <?php
    $category = $_GET['category'] ?? '';
    $sql = "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id";
    $params = [];

    if ($category) {
        $sql .= " WHERE n.category = :cat";
        $params[':cat'] = $category;
    }

    $sql .= " ORDER BY n.created_at DESC LIMIT 20";
    $notes = db()->fetchAll($sql, $params);

    foreach ($notes as $note) {
        echo renderNoteCard($note);
    }
    ?>
</div>
<div class="load-more" id="feed-load-more">
    <button class="load-more-btn" onclick="loadMoreFeed()">加载更多</button>
</div>

<script>
let feedPage = 1;
async function loadMoreFeed() {
    feedPage++;
    const category = new URLSearchParams(window.location.search).get('category') || '';
    try {
        const res = await fetch(`index.php?api=notes&action=feed&page=${feedPage}&category=${category}`);
        const data = await res.json();
        document.getElementById('feed-container').insertAdjacentHTML('beforeend', data.html);
        if (!data.has_more) {
            document.getElementById('feed-load-more').innerHTML = '<p class="no-more">没有更多了</p>';
        }
    } catch(e) {
        console.error(e);
    }
}
</script>
