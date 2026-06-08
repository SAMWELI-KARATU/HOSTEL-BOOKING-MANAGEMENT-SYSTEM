<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && $user['status'] === 'active') {
        $_SESSION['user_id'] = $user['id'];
        redirect('dashboard.php');
    }
    flash('Invalid login details or inactive account.', 'error');
}

render_header('Login');
?>
<section class="panel narrow">
    <h1>Login</h1>
    <form method="post" class="form">
        <label>Email <input type="email" name="email" required></label>
        <label>Password <input type="password" name="password" required></label>
        <button class="button primary" type="submit">Login</button>
    </form>
</section>
<?php render_footer(); ?>

