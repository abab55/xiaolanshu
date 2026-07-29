<?php
require __DIR__ . '/../../includes/header_simple.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<div class="admin-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1>评论管理</h1>
        <div class="admin-table-wrap">
            <div class="admin-toolbar">
                <div class="admin-search-box">
                    <input type="text" id="commentSearch" placeholder="搜索评论内容..." onkeydown="if(event.key==='Enter')loadComments(1)">
                    <button class="admin-search-btn" onclick="loadComments(1)">搜索</button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="admin-table">
                    <thead><tr>
                        <th>ID</th><th>评论内容</th><th>评论者</th><th>所属笔记</th><th>点赞</th><th>时间</th><th>操作</th>
                    </tr></thead>
                    <tbody id="commentTbody"></tbody>
                </table>
            </div>
            <div class="admin-pagination" id="commentPagination"></div>
        </div>
    </div>
</div>
<script>
var commentPage = 1;
async function loadComments(p) {
    commentPage = p || commentPage;
    var q = document.getElementById('commentSearch').value;
    var r = await fetch('index.php?api=admin&action=comments&p='+commentPage+'&q='+encodeURIComponent(q));
    var d = await r.json();
    var tb = document.getElementById('commentTbody');
    if (!d.comments.length) { tb.innerHTML = '<tr><td colspan="7" class="admin-empty">暂无数据</td></tr>'; document.getElementById('commentPagination').innerHTML=''; return; }
    tb.innerHTML = d.comments.map(function(c){
        var content = c.content.length > 40 ? c.content.substring(0,40)+'...' : c.content;
        return '<tr>'+
            '<td>'+c.id+'</td>'+
            '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="'+c.content.replace(/"/g,'&quot;')+'">'+content+'</td>'+
            '<td>'+c.author_name+'</td>'+
            '<td><a href="index.php?page=note&id='+c.note_id+'" target="_blank">'+c.note_title+'</a></td>'+
            '<td>'+c.likes_count+'</td>'+
            '<td style="font-size:11px;color:#999">'+c.created_at+'</td>'+
            '<td><button class="admin-btn admin-btn-danger admin-btn-sm" onclick="deleteComment('+c.id+')">删除</button></td>'+
            '</tr>';
    }).join('');
    var pages = '';
    for (var i=1;i<=d.pages;i++) pages += '<button '+(i===d.page?'disabled':'')+' onclick="loadComments('+i+')">'+i+'</button>';
    document.getElementById('commentPagination').innerHTML = '<span>共 '+d.total+' 条</span>'+pages;
}
async function deleteComment(cid) {
    if (!confirm('确定要删除这条评论吗？')) return;
    var r = await fetch('index.php?api=admin&action=comment_delete',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'comment_id='+cid
    });
    var d = await r.json();
    if (d.success) loadComments();
}
loadComments(1);
</script>
<?php require __DIR__ . '/../../includes/footer_simple.php'; ?>
