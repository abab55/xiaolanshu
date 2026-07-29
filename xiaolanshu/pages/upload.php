<div class="upload-page">
    <div class="upload-card">
        <h2>发布笔记</h2>
        <p class="upload-subtitle">分享你的精彩瞬间</p>

        <form id="uploadForm" onsubmit="handleUpload(event)" enctype="multipart/form-data">
            <div class="upload-images-section">
                <div class="upload-images-grid" id="imageGrid">
                    <label class="upload-image-add" for="imageInput">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2">
                            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>添加图片</span>
                    </label>
                    <input type="file" id="imageInput" name="images[]" accept="image/*" multiple style="display:none" onchange="previewImages(event)">
                </div>
                <p class="upload-hint">最多上传9张图片，支持jpg/png/gif/webp</p>
            </div>

            <div class="form-group">
                <input type="text" name="title" placeholder="添加标题 (必填)" required maxlength="200">
            </div>

            <div class="form-group">
                <textarea name="content" placeholder="分享你的想法... (必填)" required maxlength="5000" rows="6"></textarea>
            </div>

            <div class="form-group">
                <input type="text" name="tags" placeholder="添加标签，用逗号分隔 (如: 美食,探店)">
            </div>

            <div class="form-row">
                <div class="form-group half">
                    <select name="category">
                        <option value="other">选择分类</option>
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
                    </select>
                </div>
                <div class="form-group half">
                    <input type="text" name="location" placeholder="添加地点">
                </div>
            </div>

            <button type="submit" class="auth-btn" id="uploadBtn">发布笔记</button>
            <div class="auth-error" id="uploadError" style="display:none"></div>
        </form>
    </div>
</div>

<script>
let imageFiles = [];
function previewImages(event) {
    const files = Array.from(event.target.files);
    if (files.length + imageFiles.length > 9) {
        alert('最多上传9张图片');
        return;
    }
    imageFiles = imageFiles.concat(files);
    renderImagePreviews();
}
function renderImagePreviews() {
    const grid = document.getElementById('imageGrid');
    grid.innerHTML = '';
    imageFiles.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'upload-preview-item';
            div.innerHTML = `
                <img src="${e.target.result}" alt="">
                <button type="button" class="remove-image-btn" onclick="removeImage(${idx})">&times;</button>
            `;
            grid.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
    if (imageFiles.length < 9) {
        const label = document.createElement('label');
        label.className = 'upload-image-add';
        label.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg><span>添加图片</span>';
        label.htmlFor = 'imageInput';
        grid.appendChild(label);
    }
}
function removeImage(idx) {
    imageFiles.splice(idx, 1);
    renderImagePreviews();
    document.getElementById('imageInput').value = '';
}

async function handleUpload(e) {
    e.preventDefault();
    const errorEl = document.getElementById('uploadError');
    const btn = document.getElementById('uploadBtn');
    errorEl.style.display = 'none';
    btn.disabled = true;
    btn.textContent = '发布中...';

    const formData = new FormData(e.target);
    formData.append('action', 'create');
    imageFiles.forEach(f => formData.append('images[]', f));

    try {
        const res = await fetch('index.php?api=notes', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error) {
            errorEl.textContent = data.error;
            errorEl.style.display = 'block';
            btn.disabled = false;
            btn.textContent = '发布笔记';
        } else {
            window.location.href = 'index.php?page=note&id=' + data.note_id;
        }
    } catch(err) {
        errorEl.textContent = '网络错误，请重试';
        errorEl.style.display = 'block';
        btn.disabled = false;
        btn.textContent = '发布笔记';
    }
}
</script>
