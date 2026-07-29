<?php
header('Content-Type: image/jpeg');
$text = $_GET['text'] ?? '笔记';
$w = min((int)($_GET['w'] ?? 400), 1200);
$h = min((int)($_GET['h'] ?? 400), 1200);

$img = imagecreatetruecolor($w, $h);
$hash = md5($text . time());
$r = hexdec(substr($hash, 0, 2));
$g = hexdec(substr($hash, 2, 2));
$b = hexdec(substr($hash, 4, 2));

// Gradient background
for ($i = 0; $i < $h; $i++) {
    $ratio = $i / $h;
    $cr = (int)($r * (1 - $ratio * 0.3) + 240 * $ratio * 0.3);
    $cg = (int)($g * (1 - $ratio * 0.3) + 245 * $ratio * 0.3);
    $cb = (int)($b * (1 - $ratio * 0.3) + 250 * $ratio * 0.3);
    $color = imagecolorallocate($img, $cr, $cg, $cb);
    imageline($img, 0, $i, $w, $i, $color);
}

// Decorative circles
$circleColor = imagecolorallocatealpha($img, 255, 255, 255, 100);
imagefilledellipse($img, $w * 0.8, $h * 0.2, $w * 0.4, $w * 0.4, $circleColor);
imagefilledellipse($img, $w * 0.2, $h * 0.7, $w * 0.3, $w * 0.3, $circleColor);

// Text
$textColor = imagecolorallocate($img, 255, 255, 255);
$lines = mb_str_split($text, 8);
$fontSize = 5;
$lineHeight = imagefontheight($fontSize) + 8;
$totalHeight = count($lines) * $lineHeight;
$y = ($h - $totalHeight) / 2;

foreach ($lines as $line) {
    $lineW = mb_strlen($line) * imagefontwidth($fontSize);
    $x = ($w - $lineW) / 2;
    imagestring($img, $fontSize, (int)$x, (int)$y, $line, $textColor);
    $y += $lineHeight;
}

imagejpeg($img, null, 85);
imagedestroy($img);
