<?php
require __DIR__ . '/../../includes/header_simple.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">
<div class="admin-wrapper">
    <?php include __DIR__ . '/_sidebar.php'; ?>
    <div class="admin-content">
        <h1>分类审核员分配</h1>
        <form method="post" action="index.php?api=admin&action=assign_reviewer" onsubmit="event.preventDefault(); assignReviewer(this);" style="max-width:500px;">
            <div style="margin-bottom:10px;">
                <label>分类：</label>
                <select name="category" required>
                    <option value="">-- 选择分类 --</option>
                    <option value="food">美食</option>
                    <option value="fashion">穿搭</option>
                    <option value="travel">旅行</option>
                    <option value="beauty">美妆</option>
                    <option value="fitness">健身</option>
                    <option value="pet">萌宠</option>
                    <option value="home">家居</option>
                    <option value="tech">数码</option>
                    <option value="reading">读书</option>
                    <option value="lifestyle">生活</option>
                    <option value="other">其他</option>
                </select>
            </div>
            <div style="margin-bottom:10px;">
                <label>审核员用户 ID：</label>
                <input type="number" name="user_id" required placeholder="输入用户 ID" style="width:100%;padding:8px;">
            </div>
            <button type="submit" class="admin-btn" style="width:100%;">分配审核员</button>
        </form>
        <h2 style="margin-top:30px;">当前分配</h2>
        <table class="admin-table" style="margin-top:10px;">
            <thead><tr><th>分类</th><th>用户 ID</th><th>操作</th></tr></thead>
            <tbody id="reviewerTbody"></tbody>
        </table>
    </div>
</div>
<script>
async function loadReviewers() {
    var r = await fetch('index.php?api=admin&action=reviewers');
    var d = await r.json();
    var tb = document.getElementById('reviewerTbody');
    tb.innerHTML = d.reviewers ? d.reviewers.map(function(x){ return '<tr><td>'+x.category+'</td><td>'+x.user_id+'</td><td><button onclick="removeReviewer('+x.id+')">删除</button></td></tr>'; }).join('') : '<tr><td colspan="3">暂无数据</td></tr>';
}
async function assignReviewer(form) {
    var fd = new FormData(form);
    var r = await fetch('index.php?api=admin&action=assign_reviewer', {method:'POST', body: fd});
    var d = await r.json();
    if (d.success) { alert('分配成功'); loadReviewers(); form.reset(); }
    else alert(d.message || '分配失败');
}
async function removeReviewer(id) {
    if (!confirm('确定删除此分配？')) return;
    var r = await fetch('index.php?api=admin&action=remove_reviewer', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'id='+id});
    var d = await r.json();
    if (d.success) loadReviewers();
}
loadReviewers();
</script>
<?php require __DIR__ . '/../../includes/footer_simple.php'; ?>
