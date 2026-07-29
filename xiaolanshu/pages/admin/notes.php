<?php
require __DIR__ . '/../../includes/header_simple.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<div class="admin-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1>笔记管理</h1>
        <div class="admin-table-wrap">
            <div class="admin-toolbar">
                <div class="admin-search-box">
                    <input type="text" id="noteSearch" placeholder="搜索笔记标题或内容..." onkeydown="if(event.key==='Enter')loadNotes(1)">
                    <button class="admin-search-btn" onclick="loadNotes(1)">搜索</button>
                </div>
            </div>
            <div style="overflow-x:auto">
                <table class="admin-table">
                    <thead><tr>
                        <th>ID</th><th>标题</th><th>作者</th><th>分类</th><th>点赞</th><th>收藏</th><th>评论</th><th>浏览</th><th>发布时间</th><th>操作</th>
                    </tr></thead>
                    <tbody id="noteTbody"></tbody>
                </table>
            </div>
            <div class="admin-pagination" id="notePagination"></div>
        </div>
    </div>
</div>
<script>
var notePage = 1;
var cats = {food:'美食',fashion:'穿搭',travel:'旅行',beauty:'美妆',fitness:'健身',pet:'萌宠',home:'家居',tech:'数码',reading:'读书',lifestyle:'生活',other:'其他'};
async function loadNotes(p) {
    notePage = p || notePage;
    var q = document.getElementById('noteSearch').value;
    var r = await fetch('index.php?api=admin&action=notes&p='+notePage+'&q='+encodeURIComponent(q));
    var d = await r.json();
    var tb = document.getElementById('noteTbody');
    if (!d.notes.length) { tb.innerHTML = '<tr><td colspan="10" class="admin-empty">暂无数据</td></tr>'; document.getElementById('notePagination').innerHTML=''; return; }
    tb.innerHTML = d.notes.map(function(n){
        return '<tr>'+
            '<td>'+n.id+'</td>'+
            '<td><a href="index.php?page=note&id='+n.id+'" target="_blank" class="admin-note-title" title="'+n.title+'">'+n.title+'</a></td>'+
            '<td>'+n.username+'</td>'+
            '<td>'+ (cats[n.category] || n.category) +'</td>'+
            '<td>'+n.likes_count+'</td>'+
            '<td>'+n.collects_count+'</td>'+
            '<td>'+n.comments_count+'</td>'+
            '<td>'+n.views_count+'</td>'+
            '<td style="font-size:11px;color:#999">'+n.created_at+'</td>'+
            '<td><button class="admin-btn admin-btn-danger admin-btn-sm" onclick="deleteNote('+n.id+',\''+n.title.replace(/'/g,"\\'")+'\')">删除</button></td>'+
            '</tr>';
    }).join('');
    var pages = '';
    for (var i=1;i<=d.pages;i++) pages += '<button '+(i===d.page?'disabled':'')+' onclick="loadNotes('+i+')">'+i+'</button>';
    document.getElementById('notePagination').innerHTML = '<span>共 '+d.total+' 条</span>'+pages;
}
async function deleteNote(nid, title) {
    if (!confirm('确定要删除笔记「'+title+'」吗？')) return;
    var r = await fetch('index.php?api=admin&action=note_delete',{
        method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'note_id='+nid
    });
    var d = await r.json();
    if (d.success) loadNotes();
}
loadNotes(1);
</script>
<?php require __DIR__ . '/../../includes/footer_simple.php'; ?>
