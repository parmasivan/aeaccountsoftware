<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$user = currentUser();
if ($user !== null) {
    header('Location: ' . ($user['role'] === 'admin' ? '/admin.php' : '/user.php'));
    exit;
}
?><!DOCTYPE html>
<html lang="ta">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AE Accounts Login</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-body">
    <main class="auth-shell">
        <section class="auth-card">
            <p class="badge">AE Accounts Login</p>
            <h1>Admin / User Login</h1>
            <p class="lead">Admin product, price, stock master maintain பண்ணுவார். User தினசரி sales/service/expense entry மட்டும் செய்வார்.</p>

            <form id="loginForm" class="form-grid top-gap">
                <input name="username" placeholder="Username" required>
                <input name="password" type="password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>

            <div class="credential-box top-gap">
                <h2>Default Login</h2>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>User:</strong> staff / user123</p>
            </div>
        </section>
    </main>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = Object.fromEntries(new FormData(event.currentTarget).entries());

            const response = await fetch('./api.php?path=login', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload),
            });

            const data = await response.json();
            if (!response.ok) {
                alert(data.error || 'Login failed');
                return;
            }

            window.location.href = data.user.role === 'admin' ? './admin.php' : './user.php';
        });
    </script>
</body>
</html>
