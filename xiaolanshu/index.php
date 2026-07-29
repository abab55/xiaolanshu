<?php
// Serve static files directly
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
if (preg_match('/\.(?:css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf|eot|webp)$/', $uri)) {
    return false;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/init.php';

$page = $_GET['page'] ?? '';
$api = $_GET['api'] ?? '';

if ($api) {
    switch ($api) {
        case 'auth':
            require __DIR__ . '/api/auth.php';
            break;
        case 'notes':
            require __DIR__ . '/api/notes.php';
            break;
        case 'interact':
            require __DIR__ . '/api/interact.php';
            break;
        case 'follow':
            require __DIR__ . '/api/follow.php';
            break;
        case 'search':
            require __DIR__ . '/api/search.php';
            break;
        case 'admin':
            require __DIR__ . '/api/admin.php';
            break;
        case 'messages':
            require __DIR__ . '/api/messages.php';
            break;
        case 'chat':
            require __DIR__ . '/api/chat.php';
            break;
        default:
            jsonError('未知API');
    }
    exit;
}

$GLOBALS['_note_page'] = ($page === 'note');
$GLOBALS['_search_page'] = ($page === 'search');
$GLOBALS['_messages_page'] = ($page === 'messages');

// Check login before outputting header, so redirect works
$loginRequiredPages = ['upload', 'chat', 'create-group', 'qrcode'];
if (in_array($page, $loginRequiredPages) || str_starts_with($page, 'admin')) {
    requireLogin();
}

require __DIR__ . '/includes/header.php';

switch ($page) {
    case 'login':
        if (isLoggedIn()) redirect('index.php');
        require __DIR__ . '/pages/login.php';
        break;
    case 'register':
        if (isLoggedIn()) redirect('index.php');
        require __DIR__ . '/pages/register.php';
        break;
    case 'note':
        require __DIR__ . '/pages/note.php';
        break;
    case 'profile':
        require __DIR__ . '/pages/profile.php';
        break;
    case 'explore':
        require __DIR__ . '/pages/explore.php';
        break;
    case 'upload':
        require __DIR__ . '/pages/upload.php';
        break;
    case 'messages':
        require __DIR__ . '/pages/messages.php';
        break;
    case 'search':
        require __DIR__ . '/pages/search.php';
        break;
    case 'system-messages':
        require __DIR__ . '/pages/system-messages.php';
        break;
    case 'chat':
        requireLogin();
        require __DIR__ . '/pages/chat.php';
        break;
    case 'create-group':
        requireLogin();
        require __DIR__ . '/pages/create-group.php';
        break;
    case 'qrcode':
        requireLogin();
        require __DIR__ . '/pages/qrcode.php';
        break;
    case 'logout':
        session_destroy();
        redirect('index.php');
        break;
    case 'admin':
    case 'admin_dashboard':
        requireAdmin();
        require __DIR__ . '/pages/admin/dashboard.php';
        break;
    case 'admin_users':
        requireAdmin();
        require __DIR__ . '/pages/admin/users.php';
        break;
    case 'admin_notes':
        requireAdmin();
        require __DIR__ . '/pages/admin/notes.php';
        break;
    case 'admin_comments':
        requireAdmin();
        require __DIR__ . '/pages/admin/comments.php';
        break;
    default:
        require __DIR__ . '/pages/feed.php';
        break;
}

require __DIR__ . '/includes/footer.php';
