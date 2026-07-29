<?php
$currentPage = 'admin';
require __DIR__ . '/../../includes/header_simple.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<div class="admin-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
            <h1 style="margin:0">数据看板</h1>
            <button class="admin-btn" style="background:#e8254d;color:#fff;font-size:12px" onclick="loadStats()">刷新数据</button>
        </div>
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card"><div class="stat-label">加载中...</div></div>
            <div class="stat-card"><div class="stat-label">加载中...</div></div>
            <div class="stat-card"><div class="stat-label">加载中...</div></div>
            <div class="stat-card"><div class="stat-label">加载中...</div></div>
        </div>
        <h3 style="margin-bottom:12px;color:#1a1a2e;font-size:15px">今日数据</h3>
        <div class="today-stats" id="todayStats">
            <div class="today-stat-card"><div class="today-num">-</div><div class="today-label">加载中...</div></div>
            <div class="today-stat-card"><div class="today-num">-</div><div class="today-label">加载中...</div></div>
            <div class="today-stat-card"><div class="today-num">-</div><div class="today-label">加载中...</div></div>
        </div>
    </div>
</div>
<script>
async function loadStats() {
    try {
        var r = await fetch('index.php?api=admin&action=stats');
        var d = await r.json();
        document.getElementById('statsGrid').innerHTML =
            '<div class="stat-card"><div class="stat-label">用户总数</div><div class="stat-value">'+d.total_users+'</div></div>'+
            '<div class="stat-card"><div class="stat-label">笔记总数</div><div class="stat-value">'+d.total_notes+'</div></div>'+
            '<div class="stat-card"><div class="stat-label">评论总数</div><div class="stat-value">'+d.total_comments+'</div></div>'+
            '<div class="stat-card"><div class="stat-label">互动总量</div><div class="stat-value">'+(d.total_likes+d.total_collections)+'</div><div class="stat-sub">点赞 '+d.total_likes+' · 收藏 '+d.total_collections+'</div></div>';
        document.getElementById('todayStats').innerHTML =
            '<div class="today-stat-card"><div class="today-num">'+d.today_users+'</div><div class="today-label">今日新增用户</div></div>'+
            '<div class="today-stat-card"><div class="today-num">'+d.today_notes+'</div><div class="today-label">今日新增笔记</div></div>'+
            '<div class="today-stat-card"><div class="today-num">'+d.today_comments+'</div><div class="today-label">今日新增评论</div></div>';
    } catch(e) {
        document.getElementById('statsGrid').innerHTML = '<div class="stat-card"><div class="stat-label">加载失败，请重试</div></div>';
    }
}
loadStats();
setInterval(loadStats, 30000);
</script>
<?php require __DIR__ . '/../../includes/footer_simple.php'; ?>
