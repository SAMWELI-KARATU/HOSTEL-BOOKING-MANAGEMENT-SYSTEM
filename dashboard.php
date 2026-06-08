<?php
require_once __DIR__ . '/config.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user['role'] === 'student') {
    if (isset($_POST['update_profile'])) {
        $stmt = db()->prepare('UPDATE users SET full_name=?, phone=?, department=?, academic_year=? WHERE id=?');
        $stmt->execute([
            trim($_POST['full_name'] ?? ''),
            trim($_POST['phone'] ?? ''),
            trim($_POST['department'] ?? ''),
            trim($_POST['academic_year'] ?? ''),
            $user['id'],
        ]);
        flash('Profile updated.');
        redirect('dashboard.php');
    }
    if (isset($_POST['change_password'])) {
        $password = $_POST['password'] ?? '';
        if (strlen($password) >= 6) {
            $stmt = db()->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            flash('Password changed.');
            redirect('dashboard.php');
        }
        flash('Password must be at least 6 characters.', 'error');
    }
}

if ($user['role'] === 'admin') {
    redirect('admin.php');
}
if ($user['role'] === 'maintenance') {
    redirect('staff.php');
}

$booking = active_booking_for((int)$user['id']);
$history = db()->prepare(
    "SELECT b.*, r.room_number, h.name hostel_name
     FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id
     WHERE b.student_id=? ORDER BY b.id DESC"
);
$history->execute([$user['id']]);

render_header('Dashboard');
?>
<section class="layout">
    <div class="panel">
        <h1>Student Profile</h1>
        <form method="post" class="form grid">
            <label>Student ID <input value="<?= e((string)$user['id']) ?>" disabled></label>
            <label>Full Name <input name="full_name" value="<?= e($user['full_name']) ?>" required></label>
            <label>Gender <input value="<?= e($user['gender']) ?>" disabled></label>
            <label>Email <input value="<?= e($user['email']) ?>" disabled></label>
            <label>Phone <input name="phone" value="<?= e($user['phone']) ?>"></label>
            <label>Faculty/Department <input name="department" value="<?= e($user['department']) ?>"></label>
            <label>Academic Year <input name="academic_year" value="<?= e($user['academic_year']) ?>"></label>
            <button class="button primary" name="update_profile" type="submit">Save Profile</button>
        </form>
        <form method="post" class="form inline">
            <input type="password" name="password" placeholder="New password">
            <button class="button" name="change_password" type="submit">Change Password</button>
        </form>
    </div>
    <aside class="panel">
        <h2>Current Allocation</h2>
        <?php if ($booking): ?>
            <p><strong><?= e($booking['hostel_name']) ?></strong>, Room <?= e($booking['room_number']) ?></p>
            <p>Status: <span class="badge"><?= e($booking['status']) ?></span></p>
        <?php else: ?>
            <p>No active booking yet.</p>
            <a class="button primary" href="hostels.php">Find Room</a>
        <?php endif; ?>
    </aside>
</section>
<section class="panel">
    <h2>Booking History</h2>
    <table>
        <tr><th>Hostel</th><th>Room</th><th>Status</th><th>Date</th></tr>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e($row['hostel_name']) ?></td>
                <td><?= e($row['room_number']) ?></td>
                <td><?= e($row['status']) ?></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php render_footer(); ?>

