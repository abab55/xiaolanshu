<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>小蓝书 - 标记我的生活</title>
    <link rel="stylesheet" href="assets/css/style.css?v=3">
</head>
<body<?= !empty($GLOBALS['_note_page']) ? ' class="note-page"' : '' ?>>
    <?php
    $page = $_GET['page'] ?? '';
    $pageTitles = [
        'explore' => '发现',
        'upload' => '发布',
        'profile' => '个人主页',
        'messages' => '消息',
        'login' => '登录',
        'register' => '注册',
        'admin' => '管理后台',
        'admin_dashboard' => '管理后台',
        'admin_users' => '管理后台',
        'admin_notes' => '管理后台',
        'admin_comments' => '管理后台',
        'system-messages' => '系统消息',
        'chat' => '聊天',
        'create-group' => '创建群聊',
        'qrcode' => '我的二维码',
    ];
    $isHome = ($page === '');
    ?>
    <?php if (!empty($GLOBALS['_note_page'])): ?>
    <nav class="top-nav note-top-nav">
        <button class="note-back-btn" onclick="history.back()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <span class="note-top-title">笔记详情</span>
        <div style="width:20px"></div>
    </nav>
    <?php elseif (!empty($GLOBALS['_search_page'])): ?>
    <nav class="top-nav note-top-nav">
        <button class="note-back-btn" onclick="history.back()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <span class="note-top-title">搜索</span>
        <div style="width:20px"></div>
    </nav>
    <?php elseif ($isHome): ?>
    <nav class="top-nav">
        <div class="top-nav-inner">
            <a href="index.php?page=search" class="nav-search-wrapper">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <span class="nav-search-input">搜索笔记、用户...</span>
            </a>
            <div class="nav-actions">
                <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                <a href="index.php?page=admin" class="nav-icon-btn" title="管理后台" style="color:#e8254d">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </a>
                <?php endif; ?>
                <a href="index.php?page=upload" class="nav-icon-btn" title="发布">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </a>
                <a href="index.php?page=profile&user=<?= currentUserId() ?>">
                    <img src="<?= h(getUserAvatar(currentUserId())) ?>" class="nav-avatar" alt="">
                </a>
                <?php else: ?>
                <a href="index.php?page=login" class="nav-login-btn">登录</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <?php else: ?>
    <?php $noBackPages = ['explore', 'messages', 'profile', 'login', 'register']; ?>
    <nav class="top-nav note-top-nav">
        <?php if (!in_array($page, $noBackPages)): ?>
        <button class="note-back-btn" onclick="history.back()">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        </button>
        <?php else: ?>
        <div style="width:20px"></div>
        <?php endif; ?>
        <span class="note-top-title"><?= ($GLOBALS['_chat_title'] ?? '') ?: ($pageTitles[$page] ?? '') ?></span>
        <?php if ($page === 'messages'): ?>
        <button class="msg-plus-btn" onclick="togglePlusMenu(event)">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </button>
        <?php elseif ($page === 'chat'): ?>
        <button class="chat-menu-btn" onclick="toggleChatMenu(event)">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
        </button>
        <?php else: ?>
        <div style="width:20px"></div>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    <main class="main-content">
