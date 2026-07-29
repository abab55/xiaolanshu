<div class="search-page">
    <div class="search-header-bar">
        <div class="search-input-wrapper">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <input type="text" id="searchInput" placeholder="搜索笔记、用户、标签..." value="<?= h($_GET['q'] ?? '') ?>" onkeydown="if(event.key==='Enter')doSearch()">
            <button class="search-btn" onclick="doSearch()">搜索</button>
        </div>
    </div>

    <?php if (!empty($_GET['q'])): ?>
    <div class="search-tabs">
        <button class="search-tab active" onclick="switchSearchTab('notes', this)">笔记</button>
        <button class="search-tab" onclick="switchSearchTab('users', this)">用户</button>
    </div>
    <div class="search-results" id="searchResults">
        <?php
        $q = $_GET['q'];
        $rnotes = db()->fetchAll(
            "SELECT n.*, u.username, u.avatar FROM notes n JOIN users u ON n.user_id = u.id
             WHERE n.title LIKE :q OR n.content LIKE :q2 OR n.tags LIKE :q3
             ORDER BY n.likes_count DESC LIMIT 20",
            [':q' => "%$q%", ':q2' => "%$q%", ':q3' => "%$q%"]
        );
        if ($rnotes):
            foreach ($rnotes as $n) echo renderNoteCard($n);
        else:
            echo '<div class="no-results"><p>未找到相关笔记</p></div>';
        endif;
        ?>
    </div>
    <div id="searchUsers" style="display:none"></div>
    <?php else: ?>
    <div class="search-hot">
        <h3>热门搜索</h3>
        <div class="hot-tags" id="hotTags">
            <p class="loading-tags">加载中...</p>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function doSearch() {
    const q = document.getElementById('searchInput').value.trim();
    if (!q) return;
    window.location.href = 'index.php?page=search&q=' + encodeURIComponent(q);
}

async function switchSearchTab(tab, btn) {
    document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    const q = '<?= h($_GET['q'] ?? '') ?>';

    if (tab === 'notes') {
        document.getElementById('searchResults').style.display = '';
        document.getElementById('searchUsers').style.display = 'none';
    } else {
        document.getElementById('searchResults').style.display = 'none';
        const usersEl = document.getElementById('searchUsers');
        usersEl.style.display = '';
        if (!usersEl.innerHTML) {
            try {
                const res = await fetch(`index.php?api=search&action=query&q=${encodeURIComponent(q)}&type=users`);
                const data = await res.json();
                if (data.results.length) {
                    usersEl.innerHTML = data.results.map(u => `
                        <a href="index.php?page=profile&user=${u.id}" class="search-user-item">
                            <img src="${u.avatar ? 'uploads/avatars/' + u.avatar : 'assets/images/default-avatar.svg'}" class="search-user-avatar" alt="">
                            <div class="search-user-info">
                                <span class="search-user-name">${u.username}</span>
                                <span class="search-user-bio">${u.bio || ''}</span>
                                <span class="search-user-followers">${formatNum(u.followers_count)} 粉丝 · ${u.notes_count} 笔记</span>
                            </div>
                        </a>
                    `).join('');
                } else {
                    usersEl.innerHTML = '<div class="no-results"><p>未找到相关用户</p></div>';
                }
            } catch(e) {}
        }
    }
}

<?php if (empty($_GET['q'])): ?>
// Load hot tags
fetch('index.php?api=search&action=hot_tags').then(r => r.json()).then(tags => {
    document.getElementById('hotTags').innerHTML = tags.map(t => `
        <a href="index.php?page=search&q=${encodeURIComponent(t)}" class="hot-tag">#${t}</a>
    `).join('');
});
<?php endif; ?>

function formatNum(n) { return n >= 10000 ? (n/10000).toFixed(1)+'万' : n; }
</script>
