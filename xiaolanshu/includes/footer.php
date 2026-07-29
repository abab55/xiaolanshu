    </main>

    <?php $hideFooter = ($GLOBALS['_note_page'] ?? false) || ($GLOBALS['_search_page'] ?? false) || (($_GET['page'] ?? '') === 'chat'); ?>
    <?php if (!$hideFooter): ?>
    <nav class="bottom-nav">
        <a href="index.php" class="bottom-nav-item <?= (!isset($_GET['page']) || $_GET['page'] === '') ? 'active' : '' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="<?= (!isset($_GET['page'])||$_GET['page']==='')?'currentColor':'none' ?>" stroke="<?= (!isset($_GET['page'])||$_GET['page']==='')?'currentColor':'#999' ?>" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            <span>首页</span>
        </a>
        <a href="index.php?page=explore" class="bottom-nav-item <?= ($_GET['page'] ?? '') === 'explore' ? 'active' : '' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="6" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>发现</span>
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="index.php?page=upload" class="bottom-nav-item center-btn">
            <div class="upload-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
        </a>
        <?php else: ?>
        <a href="index.php?page=login" class="bottom-nav-item center-btn">
            <div class="upload-icon-circle">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </div>
        </a>
        <?php endif; ?>
        <a href="index.php?page=messages" class="bottom-nav-item <?= ($_GET['page'] ?? '') === 'messages' ? 'active' : '' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <span>消息</span>
        </a>
        <?php if (isLoggedIn()): ?>
        <a href="index.php?page=profile&user=<?= currentUserId() ?>" class="bottom-nav-item <?= ($_GET['page'] ?? '') === 'profile' ? 'active' : '' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>我的</span>
        </a>
        <?php else: ?>
        <a href="index.php?page=login" class="bottom-nav-item <?= ($_GET['page'] ?? '') === 'login' ? 'active' : '' ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>我的</span>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <script>window.XLS = {loggedIn: <?= isLoggedIn() ? 'true' : 'false' ?>, userId: <?= isLoggedIn() ? currentUserId() : 'null' ?>};</script>
    <script src="assets/js/main.js"></script>
</body>
</html>
