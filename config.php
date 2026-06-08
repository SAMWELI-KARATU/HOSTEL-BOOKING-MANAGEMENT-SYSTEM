<?php
declare(strict_types=1);

session_start();

const DB_HOST = 'localhost';
const DB_NAME = 'hostel_booking';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function require_login(?string $role = null): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    if ($user['status'] !== 'active') {
        session_destroy();
        redirect('login.php?error=inactive');
    }
    if ($role !== null && $user['role'] !== $role) {
        redirect('dashboard.php');
    }
    return $user;
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function consume_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function notify_user(int $userId, string $title, string $message): void
{
    $stmt = db()->prepare('INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $title, $message]);
}

function active_booking_for(int $studentId): ?array
{
    $stmt = db()->prepare(
        "SELECT b.*, r.room_number, h.name AS hostel_name
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN hostels h ON h.id = r.hostel_id
         WHERE b.student_id = ? AND b.status IN ('pending_payment', 'confirmed', 'approved')
         ORDER BY b.id DESC LIMIT 1"
    );
    $stmt->execute([$studentId]);
    return $stmt->fetch() ?: null;
}

function render_header(string $title): void
{
    $user = current_user();
    $flash = consume_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> - Hostel Booking</title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
    <header class="topbar">
        <a class="brand" href="dashboard.php">Hostel Booking</a>
        <nav>
            <?php if ($user): ?>
                <a href="dashboard.php">Dashboard</a>
                <?php if ($user['role'] === 'student'): ?>
                    <a href="hostels.php">Hostels</a>
                    <a href="payments.php">Payments</a>
                    <a href="maintenance.php">Maintenance</a>
                    <a href="notifications.php">Notifications</a>
                <?php elseif ($user['role'] === 'admin'): ?>
                    <a href="admin.php">Admin</a>
                <?php else: ?>
                    <a href="staff.php">Tasks</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>
    <main class="page">
        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}

