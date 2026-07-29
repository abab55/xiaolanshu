<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="4" fill="#1877F2"/>
                    <path d="M7 8h10M7 12h8M7 16h6" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>注册小蓝书</h1>
            <p class="auth-subtitle">加入我们，分享你的精彩生活</p>
        </div>
        <form class="auth-form" id="registerForm" onsubmit="handleRegister(event)">
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名 (2-20个字符)" required maxlength="20">
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="邮箱" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码 (至少6个字符)" required minlength="6">
            </div>
            <div class="form-group">
                <input type="password" name="password2" placeholder="确认密码" required>
            </div>
            <button type="submit" class="auth-btn">注册</button>
            <div class="auth-error" id="registerError" style="display:none"></div>
        </form>
        <div class="auth-footer">
            <p>已有账号？<a href="index.php?page=login">立即登录</a></p>
        </div>
    </div>
</div>

<script>
async function handleRegister(e) {
    e.preventDefault();
    const errorEl = document.getElementById('registerError');
    errorEl.style.display = 'none';
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'register');
    try {
        const res = await fetch('index.php?api=auth', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.error) {
            errorEl.textContent = data.error;
            errorEl.style.display = 'block';
        } else {
            window.location.href = 'index.php';
        }
    } catch(err) {
        errorEl.textContent = '网络错误，请重试';
        errorEl.style.display = 'block';
    }
}
</script>
