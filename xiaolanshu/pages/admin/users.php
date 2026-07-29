<?php
require __DIR__ . '/../../includes/header_simple.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<div class="admin-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1>用户管理</h1>
        <div class="admin-table-wrap">
            <div class="admin-toolbar">
                <div class="admin-search-box">
                    <input type="text" id="userSearch" placeholder="搜索用户名或邮箱..." onkeydown="if(event.key==='Enter')loadUsers(1)">
                    <button class="admin-search-btn" onclick="loadUsers(1)">搜索</button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="admin-table">
                    <thead><tr>
                        <th>ID</th><th>头像</th><th>用户名</th><th>邮箱</th><th>笔记数</th><th>粉丝</th><th>角色</th><th>状态</th><th>注册时间</th><th>操作</th>
                    </tr></thead>
                    <tbody id="userTbody"></tbody>
                </table>
            </div>
            <div class="admin-pagination" id="userPagination"></div>
        </div>
    </div>
</div>
<script>
var userPage = 1;
async function loadUsers(p) {
    userPage = p || userPage;
    var q = document.getElementById('userSearch').value;
    var r = await fetch('index.php?api=admin&action=users&p='+userPage+'&q='+encodeURIComponent(q));
    var d = await r.json();
    var tb = document.getElementById('userTbody');
    if (!d.users.length) { tb.innerHTML = '<tr><td colspan="10" class="admin-empty">暂无数据</td></tr>'; document.getElementById('userPagination').innerHTML=''; return; }
    tb.innerHTML = d.users.map(function(u){
        var avatar = u.avatar ? 'uploads/avatars/'+u.avatar : 'assets/images/default-avatar.svg';
        var roleBadge = u.is_admin == 1 ? '<span class="badge badge-admin">管理员</span>' : '<span class="badge badge-success">普通用户</span>';
        var statusBadge = u.status == 1 ? '<span class="badge badge-success">正常</span>' : '<span class="badge badge-danger">已封禁</span>';
        var toggleBtn = u.is_admin == 1 ? '' : (
            u.status == 1
            ? '<button class="admin-btn admin-btn-warning admin-btn-sm" onclick="toggleUserStatus('+u.id+')">封禁</button>'
            : '<button class="admin-btn admin-btn-sm" style="background:#e8f5e9;color:#2e7d32" onclick="toggleUserStatus('+u.id+')">解封</button>'
        );
        var delBtn = u.is_admin == 1 ? '' : '<button class="admin-btn admin-btn-danger admin-btn-sm" onclick="deleteUser('+u.id+',\''+u.username+'\')">删除</button>';
        return '<tr>'+
            '<td>'+u.id+'</td>'+
            '<td><img src="'+avatar+'" class="admin-user-avatar" alt=""></td>'+
            '<td>'+u.username+'</td>'+
            '<td>'+u.email+'</td>'+
            '<td>'+u.notes_count+'</td>'+
            '<td>'+u.followers_count+'</td>'+
            '<td>'+roleBadge+'</td>'+
            '<td>'+statusBadge+'</td>'+
            '<td style="font-size:11px;color:#999">'+u.created_at+'</td>'+
            '<td style="white-space:nowrap">'+toggleBtn+' '+delBtn+'</td>'+
            '</tr>';
    }).join('');
    var pages = '';
    for (var i=1;i<=d.pages;i++) pages += '<button '+(i===d.page?'disabled':'')+' onclick="loadUsers('+i+')">'+i+'</button>';
    document.getElementById('userPagination').innerHTML = '<span>共 '+d.total+' 条</span>'+pages;
}
async function toggleUserStatus(uid) {
    if (!confirm('确定要切换该用户状态吗？')) return;
    var r = await fetch('index.php?api=admin&action=user_toggle_status',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'user_id='+uid
    });
    var d = await r.json();
    if (d.success) loadUsers();
}
async function deleteUser(uid, name) {
    if (!confirm('确定要删除用户「'+name+'」吗？此操作不可撤销，将删除该用户的所有笔记、评论、点赞、收藏！')) return;
    var r = await fetch('index.php?api=admin&action=user_delete',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'user_id='+uid
    });
    var d = await r.json();
    if (d.success) loadUsers();
}
loadUsers(1);
</script>
<?php require __DIR__ . '/../../includes/footer_simple.php'; ?>
