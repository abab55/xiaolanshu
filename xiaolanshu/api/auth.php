<?php
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            jsonError('请输入用户名和密码');
        }

        $user = db()->fetch(
            "SELECT * FROM users WHERE username = :u OR email = :e",
            [':u' => $username, ':e' => $username]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            jsonError('用户名或密码错误');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        jsonResponse(['success' => true, 'message' => '登录成功', 'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'avatar' => getUserAvatar($user['id']),
        ]]);
        break;

    case 'register':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            jsonError('请填写所有必填字段');
        }
        if (mb_strlen($username) < 2 || mb_strlen($username) > 20) {
            jsonError('用户名需要2-20个字符');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonError('请输入有效的邮箱地址');
        }
        if (strlen($password) < 6) {
            jsonError('密码至少需要6个字符');
        }
        if ($password !== $password2) {
            jsonError('两次密码不一致');
        }

        $exist = db()->fetch("SELECT id FROM users WHERE username = :u OR email = :e", [
            ':u' => $username, ':e' => $email
        ]);
        if ($exist) {
            jsonError('用户名或邮箱已被注册');
        }

        $uid = db()->insert(
            "INSERT INTO users (username, email, password) VALUES (:u, :e, :p)",
            [':u' => $username, ':e' => $email, ':p' => password_hash($password, PASSWORD_DEFAULT)]
        );

        // Generate avatar
        $initial = mb_substr($username, 0, 1);
        $img = generatePlaceholderImage($initial, 200, 200);
        $avatarDir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
        imagejpeg($img, "$avatarDir/avatar_$uid.jpg", 85);
        imagedestroy($img);
        db()->query("UPDATE users SET avatar = :a WHERE id = :i", [':a' => "avatar_$uid.jpg", ':i' => $uid]);

        $_SESSION['user_id'] = $uid;
        $_SESSION['username'] = $username;
        jsonResponse(['success' => true, 'message' => '注册成功']);
        break;

    case 'update_profile':
        requireLogin();
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $gender = $_POST['gender'] ?? '';
        $birthday = $_POST['birthday'] ?? '';

        db()->query("UPDATE users SET bio = :b, location = :l, gender = :g, birthday = :bd WHERE id = :id", [
            ':b' => $bio, ':l' => $location, ':g' => $gender, ':bd' => $birthday, ':id' => currentUserId()
        ]);

        // Handle avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $ext = in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp']) ? $ext : 'jpg';
            $filename = 'avatar_' . currentUserId() . '_' . time() . '.' . $ext;
            $avatarDir = __DIR__ . '/../uploads/avatars';
            if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
            move_uploaded_file($_FILES['avatar']['tmp_name'], "$avatarDir/$filename");
            db()->query("UPDATE users SET avatar = :a WHERE id = :id", [':a' => $filename, ':id' => currentUserId()]);
        }

        jsonResponse(['success' => true, 'message' => '资料已更新']);
        break;

    case 'me':
        if (!isLoggedIn()) jsonResponse(['loggedIn' => false]);
        $user = currentUser();
        jsonResponse([
            'loggedIn' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'avatar' => getUserAvatar($user['id']),
            ]
        ]);
        break;

    default:
        jsonError('未知操作');
}
