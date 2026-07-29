<?php
if (!isLoggedIn()) { header('Location: index.php?page=login'); exit; }
$user = currentUser();
?>

<div class="qrcode-page">
    <div class="qrcode-card">
        <div class="qrcode-user-info">
            <img src="<?= h(getUserAvatar($user['id'])) ?>" class="qrcode-avatar" alt="">
            <h3 class="qrcode-username"><?= h($user['username']) ?></h3>
            <p class="qrcode-bio"><?= h($user['bio'] ?: '这个人很懒，什么都没写') ?></p>
        </div>
        <div class="qrcode-img-wrapper">
            <div class="qrcode-img" id="qrcodeEl"></div>
        </div>
        <p class="qrcode-tip">扫一扫二维码，添加我为好友</p>
    </div>
</div>

<script>
// Simple QR code simulation using canvas
(function() {
    const url = window.location.origin + '/index.php?page=profile&user=<?= $user['id'] ?>';
    const size = 200;
    const modules = 21;
    const scale = Math.floor(size / modules);
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');

    // Simple hash-based QR pattern
    function hashChar(c, i) {
        let h = c.charCodeAt(0) + i * 31;
        return (h % 3) > 0;
    }

    ctx.fillStyle = '#fff';
    ctx.fillRect(0, 0, size, size);
    ctx.fillStyle = '#000';

    for (let r = 0; r < modules; r++) {
        for (let c = 0; c < modules; c++) {
            // Position patterns (corners)
            if ((r < 7 && c < 7) || (r < 7 && c > modules - 8) || (r > modules - 8 && c < 7)) {
                if ((r === 0 || r === 6 || c === 0 || c === 6) || (r >= 2 && r <= 4 && c >= 2 && c <= 4)) {
                    ctx.fillRect(c * scale, r * scale, scale, scale);
                }
                continue;
            }
            const idx = r * modules + c;
            const ch = url.charCodeAt(idx % url.length) || 0;
            if (hashChar(String.fromCharCode(ch), idx)) {
                ctx.fillRect(c * scale, r * scale, scale, scale);
            }
        }
    }

    document.getElementById('qrcodeEl').appendChild(canvas);
})();
</script>
