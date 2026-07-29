<div class="auth-page">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="4" fill="#1877F2"/>
                    <path d="M7 8h10M7 12h8M7 16h6" stroke="white" stroke-width="2" stroke-linecap="round"/>
                </svg>
            </div>
            <h1>登录小蓝书</h1>
            <p class="auth-subtitle">标记我的生活，发现美好世界</p>
        </div>
        <form class="auth-form" id="loginForm" onsubmit="handleLogin(event)">
            <div class="form-group">
                <input type="text" name="username" placeholder="用户名或邮箱" required autocomplete="username">
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="密码" required autocomplete="current-password">
            </div>
            <button type="submit" class="auth-btn">登录</button>
            <div class="auth-error" id="loginError" style="display:none"></div>
        </form>
        <div class="auth-footer">
            <p>还没有账号？<a href="index.php?page=register">立即注册</a></p>
            <p class="auth-demo">演示账号: xiaolan@test.com / 123456</p>
        </div>
    </div>
</div>

<script>
async function handleLogin(e) {
    e.preventDefault();
    const errorEl = document.getElementById('loginError');
    errorEl.style.display = 'none';
    const form = e.target;
    const formData = new FormData(form);
    formData.append('action', 'login');
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
