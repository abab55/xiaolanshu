<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = db()->getDb();
$db->exec("
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    is_admin INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    avatar TEXT DEFAULT '',
    bio TEXT DEFAULT '',
    gender TEXT DEFAULT '',
    birthday TEXT DEFAULT '',
    location TEXT DEFAULT '',
    followers_count INTEGER DEFAULT 0,
    following_count INTEGER DEFAULT 0,
    notes_count INTEGER DEFAULT 0,
    collects_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    content TEXT DEFAULT '',
    images TEXT DEFAULT '[]',
    video TEXT DEFAULT '',
    tags TEXT DEFAULT '',
    location TEXT DEFAULT '',
    category TEXT DEFAULT 'other',
    likes_count INTEGER DEFAULT 0,
    comments_count INTEGER DEFAULT 0,
    collects_count INTEGER DEFAULT 0,
    views_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS likes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    note_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (note_id) REFERENCES notes(id),
    UNIQUE(user_id, note_id)
);

CREATE TABLE IF NOT EXISTS collections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    note_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (note_id) REFERENCES notes(id),
    UNIQUE(user_id, note_id)
);

CREATE TABLE IF NOT EXISTS comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    note_id INTEGER NOT NULL,
    parent_id INTEGER DEFAULT 0,
    content TEXT NOT NULL,
    likes_count INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (note_id) REFERENCES notes(id)
);

CREATE TABLE IF NOT EXISTS follows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    follower_id INTEGER NOT NULL,
    following_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id),
    FOREIGN KEY (following_id) REFERENCES users(id),
    UNIQUE(follower_id, following_id)
);

CREATE TABLE IF NOT EXISTS comment_likes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    comment_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (comment_id) REFERENCES comments(id),
    UNIQUE(user_id, comment_id)
);

CREATE INDEX IF NOT EXISTS idx_notes_user ON notes(user_id);
CREATE INDEX IF NOT EXISTS idx_notes_created ON notes(created_at);
CREATE INDEX IF NOT EXISTS idx_likes_note ON likes(note_id);
CREATE INDEX IF NOT EXISTS idx_collections_note ON collections(note_id);
CREATE INDEX IF NOT EXISTS idx_comments_note ON comments(note_id);
CREATE INDEX IF NOT EXISTS idx_follows_follower ON follows(follower_id);
CREATE INDEX IF NOT EXISTS idx_follows_following ON follows(following_id);
CREATE INDEX IF NOT EXISTS idx_notes_category ON notes(category);

CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_user_id INTEGER NOT NULL DEFAULT 0,
    to_user_id INTEGER NOT NULL DEFAULT 0,
    content TEXT DEFAULT '',
    type TEXT DEFAULT 'chat',
    is_read INTEGER DEFAULT 0,
    group_id INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS groups_chat (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    owner_id INTEGER NOT NULL,
    avatar TEXT DEFAULT '',
    group_number TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT DEFAULT 'member',
    UNIQUE(group_id, user_id)
);

CREATE TABLE IF NOT EXISTS friend_requests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    from_user_id INTEGER NOT NULL,
    to_user_id INTEGER NOT NULL,
    message TEXT DEFAULT '',
    status TEXT DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME
);
");

// Seed test data
$userCount = db()->fetch("SELECT COUNT(*) as cnt FROM users")['cnt'];
if ($userCount == 0) {
    $password = password_hash('123456', PASSWORD_DEFAULT);

    $users = [
        ['小蓝酱', 'xiaolan@test.com', '热爱生活，分享美好日常', '', 1],
        ['旅行达人小明', 'xiaoming@test.com', '走遍世界每个角落', '', 0],
        ['美食探店家', 'foodie@test.com', '发现城市美食地图', '', 0],
        ['穿搭日记', 'fashion@test.com', '每天都要美美哒', '', 0],
        ['摄影老法师', 'photo@test.com', '用镜头记录生活', '', 0],
        ['健身日记', 'fitness@test.com', '自律给我自由', '', 0],
        ['读书笔记', 'reading@test.com', '书中自有黄金屋', '', 0],
        ['宠物乐园', 'pet@test.com', '猫狗双全的幸福生活', '', 0],
        ['美妆种草姬', 'beauty@test.com', '好物分享，种草拔草', '', 0],
        ['家居灵感', 'home@test.com', '打造理想中的家', '', 0],
    ];

    $userIds = [];
    foreach ($users as $u) {
        $uid = db()->insert(
            "INSERT INTO users (username, email, password, bio, is_admin) VALUES (:u, :e, :p, :b, :a)",
            [':u' => $u[0], ':e' => $u[1], ':p' => $password, ':b' => $u[2], ':a' => $u[4]]
        );
        $userIds[] = $uid;

        // Generate avatar
        $initial = mb_substr($u[0], 0, 1);
        $img = generatePlaceholderImage($initial, 200, 200);
        $avatarDir = __DIR__ . '/../uploads/avatars';
        if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);
        imagejpeg($img, "$avatarDir/avatar_$uid.jpg", 85);
        imagedestroy($img);
        db()->query("UPDATE users SET avatar = :a WHERE id = :i", [':a' => "avatar_$uid.jpg", ':i' => $uid]);
    }

    // Seed notes
    $sampleNotes = [
        ['今日份早餐打卡！牛油果吐司配拿铁，开启元气满满的一天', '最近爱上了做早餐，简单又健康的牛油果吐司，配上自制拿铁，幸福感爆棚！#早餐 #健康生活 #美食分享', '早餐,美食,健康', 'food'],
        ['周末探店：藏在巷子里的宝藏咖啡馆', '朋友推荐的一家小众咖啡馆，环境超赞，手冲咖啡一绝！还可以撸猫，呆了一下午不想走... #咖啡馆 #周末探店', '探店,咖啡,周末', 'food'],
        ['OOTD | 初秋温柔系穿搭', '最近降温了，终于可以穿毛衣啦！米色毛衣+棕色半裙，温柔又气质。搭配一双马丁靴，甜酷风拿捏住！#穿搭 #OOTD #秋季穿搭', '穿搭,OOTD,秋季', 'fashion'],
        ['杭州西湖赏秋攻略，美到窒息！', '十月的西湖真的太美了！枫叶红了，银杏黄了，随手一拍都是大片。分享几条最佳赏秋路线~ #杭州 #西湖 #旅行攻略', '旅行,西湖,秋天', 'travel'],
        ['新手养猫指南 | 接猫前必看！', '作为养猫两年的铲屎官，整理了新手接猫前需要准备的东西和注意事项。猫砂、猫粮、疫苗、绝育...一篇搞定！#养猫 #新手养猫 #萌宠', '养猫,攻略,萌宠', 'pet'],
        ['一周健身计划分享 | 在家也能练出好身材', '不需要去健身房！分享我的居家健身计划，每天30分钟，坚持一个月就能看到变化。#健身 #居家锻炼 #自律', '健身,运动,自律', 'fitness'],
        ['读《百年孤独》有感：孤独是人生的必修课', '重读马尔克斯的百年孤独，有了不一样的感悟。我们每个人都是一座孤岛，但文字可以连接彼此。#读书 #百年孤独 #书评', '读书,书评,文学', 'reading'],
        ['35平米小户型改造，收纳翻倍！', '小户型也能拥有大空间！分享我的收纳好物和空间利用技巧，榨干每一寸空间。#小户型 #收纳 #家居改造', '家居,收纳,改造', 'home'],
        ['秋季护肤routine | 干皮救星来了', '换季皮肤干燥起皮？分享我的秋季护肤流程，从洁面到面霜，每一步都安排明白！#护肤 #秋季护肤 #美妆', '护肤,美妆,秋季', 'beauty'],
        ['手机摄影技巧：用手机拍出单反感', '不用买相机！学会这些手机摄影构图技巧，朋友圈点赞翻倍。#摄影 #手机摄影 #构图', '摄影,技巧,教程', 'tech'],
        ['三亚度假vlog | 蓝天白云沙滩', '终于请到年假去三亚啦！椰林、沙滩、海鲜大餐，这才是生活啊~ #三亚 #度假 #海岛', '旅行,三亚,度假', 'travel'],
        ['双十一购物清单 | 这些好物闭眼入！', '整理了一波双十一必买清单，从美妆到家居，都是自己用过的良心推荐！#双十一 #购物清单 #好物推荐', '购物,好物,双十一', 'lifestyle'],
        ['自制提拉米苏 | 零失败配方', '超简单的提拉米苏做法，不用烤箱！入口即化，比买的还好吃~ #烘焙 #提拉米苏 #甜品', '烘焙,甜品,教程', 'food'],
        ['通勤妆容5分钟搞定 | 懒人必备', '早八人必备的通勤妆容教程，基础步骤+单品推荐，每天多睡半小时！#化妆 #通勤妆 #懒人', '化妆,美妆,教程', 'beauty'],
        ['京都红叶季 | 此生必去的赏枫圣地', '去年秋天去了京都，红叶季真的太震撼了。附详细攻略，包括机票、住宿、景点推荐。#京都 #红叶 #日本旅行', '旅行,日本,红叶', 'travel'],
        ['租房改造 | 500元让出租屋变温馨小窝', '刚毕业租房预算有限？教你用最少的钱改造出租屋，提升生活幸福感！#租房改造 #低成本 #家居', '家居,改造,租房', 'home'],
        ['跳绳减肥真的有用吗？我的30天打卡记录', '坚持跳绳一个月，体重掉了8斤！每天2000个，配合饮食控制...#减肥 #跳绳 #打卡', '健身,减肥,打卡', 'fitness'],
        ['iPad无纸化学习 | 效率翻倍的宝藏APP', '买了iPad不知道干嘛？分享我的学习型iPad配置，这些APP让学习效率起飞！#iPad #学习 #效率', '学习,效率,iPad', 'tech'],
        ['上海迪士尼攻略 | 一天刷完所有项目', '周末去了迪士尼，总结了一日游完美路线，不用买早享卡也能玩得尽兴！#迪士尼 #上海 #攻略', '旅行,迪士尼,攻略', 'travel'],
        ['治愈系桌面布置 | 办公学习两不误', '分享我的书桌布置，收纳好物+氛围灯，打造治愈系工作学习空间。#桌面 #布置 #治愈', '家居,桌面,治愈', 'lifestyle'],
    ];

    foreach ($sampleNotes as $idx => $note) {
        $uid = $userIds[$idx % count($userIds)];
        $nid = db()->insert(
            "INSERT INTO notes (user_id, title, content, tags, category, images) VALUES (:uid, :t, :c, :tags, :cat, :imgs)",
            [':uid' => $uid, ':t' => $note[0], ':c' => $note[1], ':tags' => $note[2], ':cat' => $note[3], ':imgs' => '[]']
        );

        // Generate note cover image
        $count = rand(1, 4);
        $imagePaths = [];
        $coverDir = __DIR__ . '/../uploads/notes';
        if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
        for ($i = 0; $i < $count; $i++) {
            $w = rand(600, 800);
            $h = rand(400, $w);
            $img = generatePlaceholderImage(mb_substr($note[0], 0, 8), $w, $h);
            $filename = "note_{$nid}_{$i}.jpg";
            imagejpeg($img, "$coverDir/$filename", 85);
            imagedestroy($img);
            $imagePaths[] = "uploads/notes/$filename";
        }
        db()->query("UPDATE notes SET images = :imgs WHERE id = :id", [
            ':imgs' => json_encode($imagePaths, JSON_UNESCAPED_UNICODE),
            ':id' => $nid
        ]);

        db()->query("UPDATE users SET notes_count = notes_count + 1 WHERE id = :id", [':id' => $uid]);
    }

    // Seed some likes
    for ($i = 0; $i < 50; $i++) {
        $uid = $userIds[array_rand($userIds)];
        $nid = rand(1, count($sampleNotes));
        try {
            db()->insert("INSERT OR IGNORE INTO likes (user_id, note_id) VALUES (:u, :n)", [':u' => $uid, ':n' => $nid]);
            db()->query("UPDATE notes SET likes_count = likes_count + 1 WHERE id = :id", [':id' => $nid]);
        } catch (Exception $e) {}
    }

    // Seed some collections
    for ($i = 0; $i < 30; $i++) {
        $uid = $userIds[array_rand($userIds)];
        $nid = rand(1, count($sampleNotes));
        try {
            db()->insert("INSERT OR IGNORE INTO collections (user_id, note_id) VALUES (:u, :n)", [':u' => $uid, ':n' => $nid]);
            db()->query("UPDATE notes SET collects_count = collects_count + 1 WHERE id = :id", [':id' => $nid]);
        } catch (Exception $e) {}
    }

    // Seed some comments
    $commentTexts = [
        '好棒呀！学到了~',
        '太实用了，收藏了！',
        '拍得好好看！',
        '同款get！',
        '可以分享链接吗？',
        '这个配色爱了爱了',
        '请问在哪里买的呀？',
        '好好看！种草了',
        '羡慕了！',
        '我也想去！',
        '太详细了，谢谢分享！',
        '保存了，以后用得上',
    ];
    for ($i = 0; $i < 40; $i++) {
        $uid = $userIds[array_rand($userIds)];
        $nid = rand(1, count($sampleNotes));
        $content = $commentTexts[array_rand($commentTexts)];
        db()->insert(
            "INSERT INTO comments (user_id, note_id, content) VALUES (:u, :n, :c)",
            [':u' => $uid, ':n' => $nid, ':c' => $content]
        );
        db()->query("UPDATE notes SET comments_count = comments_count + 1 WHERE id = :id", [':id' => $nid]);
    }

    // Seed some follows
    for ($i = 0; $i < 25; $i++) {
        $fid = $userIds[array_rand($userIds)];
        $tid = $userIds[array_rand($userIds)];
        if ($fid == $tid) continue;
        try {
            db()->insert("INSERT OR IGNORE INTO follows (follower_id, following_id) VALUES (:f, :t)", [':f' => $fid, ':t' => $tid]);
            db()->query("UPDATE users SET followers_count = followers_count + 1 WHERE id = :id", [':id' => $tid]);
            db()->query("UPDATE users SET following_count = following_count + 1 WHERE id = :id", [':id' => $fid]);
        } catch (Exception $e) {}
    }
}
