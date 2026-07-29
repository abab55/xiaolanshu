<div class="admin-sidebar">
    <div class="admin-sidebar-header">
        <h2>小蓝书后台</h2>
        <span>管理员: <?= h(currentUser()['username']) ?></span>
    </div>
    <nav class="admin-nav">
        <a href="index.php?page=admin" class="<?= ($_GET['page'] ?? '') === 'admin' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            数据看板
        </a>
        <a href="index.php?page=admin_users" class="<?= ($_GET['page'] ?? '') === 'admin_users' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            用户管理
        </a>
        <a href="index.php?page=admin_notes" class="<?= ($_GET['page'] ?? '') === 'admin_notes' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            笔记管理
        </a>
        <a href="index.php?page=admin_comments" class="<?= ($_GET['page'] ?? '') === 'admin_comments' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            评论管理
        </a>
    </nav>
    <div style="padding:16px;border-top:1px solid rgba(255,255,255,0.1);margin-top:auto">
        <a href="index.php" style="color:rgba(255,255,255,0.5);font-size:12px">返回前台</a>
    </div>
</div>
