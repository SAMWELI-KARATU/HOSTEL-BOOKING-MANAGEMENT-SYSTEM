<?php
require_once __DIR__ . '/config.php';
$user = require_login('admin');
$tab = $_GET['tab'] ?? 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add_hostel') {
        db()->prepare('INSERT INTO hostels (name, gender_category, location, capacity, facilities, description) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$_POST['name'], $_POST['gender_category'], $_POST['location'], (int)$_POST['capacity'], $_POST['facilities'], $_POST['description']]);
        flash('Hostel added.');
    } elseif ($action === 'add_room') {
        $capacity = (int)$_POST['capacity'];
        db()->prepare('INSERT INTO rooms (hostel_id, room_number, capacity, available_spaces, price) VALUES (?, ?, ?, ?, ?)')
            ->execute([(int)$_POST['hostel_id'], $_POST['room_number'], $capacity, $capacity, (float)$_POST['price']]);
        flash('Room added.');
    } elseif ($action === 'booking_status') {
        db()->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$_POST['status'], (int)$_POST['booking_id']]);
        flash('Booking updated.');
    } elseif ($action === 'payment_status') {
        db()->prepare('UPDATE payments SET status=? WHERE id=?')->execute([$_POST['status'], (int)$_POST['payment_id']]);
        flash('Payment updated.');
    } elseif ($action === 'assign_maintenance') {
        db()->prepare('UPDATE maintenance_requests SET assigned_to=?, status="Assigned" WHERE id=?')->execute([(int)$_POST['assigned_to'], (int)$_POST['request_id']]);
        notify_user((int)$_POST['student_id'], 'Maintenance assigned', 'Your maintenance request has been assigned to staff.');
        flash('Maintenance request assigned.');
    } elseif ($action === 'send_announcement') {
        $users = db()->query('SELECT id FROM users WHERE status="active"')->fetchAll();
        foreach ($users as $target) {
            notify_user((int)$target['id'], $_POST['title'], $_POST['message']);
        }
        flash('Announcement sent.');
    } elseif ($action === 'student_status') {
        db()->prepare('UPDATE users SET status=? WHERE id=? AND role="student"')->execute([$_POST['status'], (int)$_POST['student_id']]);
        flash('Student status updated.');
    }
    redirect('admin.php?tab=' . urlencode($tab));
}

$counts = [
    'students' => db()->query('SELECT COUNT(*) c FROM users WHERE role="student"')->fetch()['c'],
    'hostels' => db()->query('SELECT COUNT(*) c FROM hostels')->fetch()['c'],
    'rooms' => db()->query('SELECT COUNT(*) c FROM rooms')->fetch()['c'],
    'bookings' => db()->query('SELECT COUNT(*) c FROM bookings')->fetch()['c'],
    'revenue' => db()->query('SELECT COALESCE(SUM(amount),0) c FROM payments WHERE status="verified"')->fetch()['c'],
];
$hostels = db()->query('SELECT * FROM hostels ORDER BY name')->fetchAll();
$staff = db()->query('SELECT id, full_name FROM users WHERE role="maintenance" AND status="active"')->fetchAll();

render_header('Admin');
?>
<section class="tabs">
    <?php foreach (['overview','students','hostels','rooms','bookings','payments','maintenance','notifications','reports'] as $item): ?>
        <a class="<?= $tab === $item ? 'active' : '' ?>" href="admin.php?tab=<?= e($item) ?>"><?= ucfirst($item) ?></a>
    <?php endforeach; ?>
</section>

<?php if ($tab === 'overview'): ?>
<section class="metrics">
    <?php foreach ($counts as $label => $value): ?>
        <div class="metric"><span><?= ucfirst($label) ?></span><strong><?= is_numeric($value) ? e((string)$value) : e($value) ?></strong></div>
    <?php endforeach; ?>
</section>
<?php elseif ($tab === 'students'): ?>
<section class="panel">
    <h1>Student Accounts</h1>
    <table>
        <tr><th>Name</th><th>Gender</th><th>Email</th><th>Status</th><th>Action</th></tr>
        <?php foreach (db()->query('SELECT * FROM users WHERE role="student" ORDER BY id DESC') as $row): ?>
        <tr>
            <td><?= e($row['full_name']) ?></td><td><?= e($row['gender']) ?></td><td><?= e($row['email']) ?></td><td><?= e($row['status']) ?></td>
            <td><form method="post" class="inline"><input type="hidden" name="action" value="student_status"><input type="hidden" name="student_id" value="<?= (int)$row['id'] ?>"><select name="status"><option>active</option><option>inactive</option></select><button class="button">Update</button></form></td>
        </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php elseif ($tab === 'hostels'): ?>
<section class="panel">
    <h1>Hostel Management</h1>
    <form method="post" class="form grid">
        <input type="hidden" name="action" value="add_hostel">
        <label>Name <input name="name" required></label>
        <label>Gender <select name="gender_category"><option>Male</option><option>Female</option></select></label>
        <label>Location <input name="location"></label>
        <label>Capacity <input type="number" name="capacity" required></label>
        <label>Facilities <input name="facilities"></label>
        <label>Description <input name="description"></label>
        <button class="button primary">Add Hostel</button>
    </form>
    <table><tr><th>Name</th><th>Gender</th><th>Location</th><th>Capacity</th></tr>
        <?php foreach ($hostels as $h): ?><tr><td><?= e($h['name']) ?></td><td><?= e($h['gender_category']) ?></td><td><?= e($h['location']) ?></td><td><?= (int)$h['capacity'] ?></td></tr><?php endforeach; ?>
    </table>
</section>
<?php elseif ($tab === 'rooms'): ?>
<section class="panel">
    <h1>Room Management</h1>
    <form method="post" class="form grid">
        <input type="hidden" name="action" value="add_room">
        <label>Hostel <select name="hostel_id"><?php foreach ($hostels as $h): ?><option value="<?= (int)$h['id'] ?>"><?= e($h['name']) ?></option><?php endforeach; ?></select></label>
        <label>Room Number <input name="room_number" required></label>
        <label>Capacity <input type="number" name="capacity" min="1" required></label>
        <label>Price <input type="number" name="price" min="0" step="0.01" required></label>
        <button class="button primary">Add Room</button>
    </form>
    <table><tr><th>Hostel</th><th>Room</th><th>Capacity</th><th>Available</th><th>Price</th></tr>
    <?php foreach (db()->query('SELECT r.*, h.name hostel FROM rooms r JOIN hostels h ON h.id=r.hostel_id ORDER BY h.name,r.room_number') as $r): ?>
        <tr><td><?= e($r['hostel']) ?></td><td><?= e($r['room_number']) ?></td><td><?= (int)$r['capacity'] ?></td><td><?= (int)$r['available_spaces'] ?></td><td><?= number_format((float)$r['price'],2) ?></td></tr>
    <?php endforeach; ?></table>
</section>
<?php elseif ($tab === 'bookings'): ?>
<section class="panel">
    <h1>Booking Management</h1>
    <table><tr><th>Student</th><th>Hostel</th><th>Room</th><th>Amount</th><th>Status</th><th>Action</th></tr>
    <?php foreach (db()->query('SELECT b.*, u.full_name, h.name hostel, r.room_number FROM bookings b JOIN users u ON u.id=b.student_id JOIN rooms r ON r.id=b.room_id JOIN hostels h ON h.id=r.hostel_id ORDER BY b.id DESC') as $b): ?>
        <tr><td><?= e($b['full_name']) ?></td><td><?= e($b['hostel']) ?></td><td><?= e($b['room_number']) ?></td><td><?= number_format((float)$b['amount'],2) ?></td><td><?= e($b['status']) ?></td><td><form method="post" class="inline"><input type="hidden" name="action" value="booking_status"><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>"><select name="status"><option>approved</option><option>confirmed</option><option>cancelled</option></select><button class="button">Save</button></form></td></tr>
    <?php endforeach; ?></table>
</section>
<?php elseif ($tab === 'payments'): ?>
<section class="panel">
    <h1>Payment Records</h1>
    <table><tr><th>Student</th><th>Amount</th><th>Method</th><th>Receipt</th><th>Status</th><th>Action</th></tr>
    <?php foreach (db()->query('SELECT p.*, u.full_name FROM payments p JOIN users u ON u.id=p.student_id ORDER BY p.id DESC') as $p): ?>
        <tr><td><?= e($p['full_name']) ?></td><td><?= number_format((float)$p['amount'],2) ?></td><td><?= e($p['method']) ?></td><td><?= e($p['receipt_number']) ?></td><td><?= e($p['status']) ?></td><td><form method="post" class="inline"><input type="hidden" name="action" value="payment_status"><input type="hidden" name="payment_id" value="<?= (int)$p['id'] ?>"><select name="status"><option>verified</option><option>pending</option><option>failed</option></select><button class="button">Save</button></form></td></tr>
    <?php endforeach; ?></table>
</section>
<?php elseif ($tab === 'maintenance'): ?>
<section class="panel">
    <h1>Maintenance Management</h1>
    <table><tr><th>Student</th><th>Issue</th><th>Status</th><th>Assign</th></tr>
    <?php foreach (db()->query('SELECT m.*, u.full_name student FROM maintenance_requests m JOIN users u ON u.id=m.student_id ORDER BY m.id DESC') as $m): ?>
        <tr><td><?= e($m['student']) ?></td><td><?= e($m['issue_type']) ?></td><td><?= e($m['status']) ?></td><td><form method="post" class="inline"><input type="hidden" name="action" value="assign_maintenance"><input type="hidden" name="request_id" value="<?= (int)$m['id'] ?>"><input type="hidden" name="student_id" value="<?= (int)$m['student_id'] ?>"><select name="assigned_to"><?php foreach ($staff as $s): ?><option value="<?= (int)$s['id'] ?>"><?= e($s['full_name']) ?></option><?php endforeach; ?></select><button class="button">Assign</button></form></td></tr>
    <?php endforeach; ?></table>
</section>
<?php elseif ($tab === 'notifications'): ?>
<section class="panel">
    <h1>Send Announcement</h1>
    <form method="post" class="form">
        <input type="hidden" name="action" value="send_announcement">
        <label>Title <input name="title" required></label>
        <label>Message <textarea name="message" required></textarea></label>
        <button class="button primary">Send</button>
    </form>
</section>
<?php else: ?>
<section class="panel">
    <h1>Reports</h1>
    <div class="cards">
        <article class="card"><h2>Hostel Occupancy</h2><p><?php $o=db()->query('SELECT SUM(occupied_spaces) occ, SUM(capacity) cap FROM rooms')->fetch(); echo (int)$o['occ'].' / '.(int)$o['cap']; ?> spaces occupied.</p></article>
        <article class="card"><h2>Revenue</h2><p><?= number_format((float)$counts['revenue'],2) ?> verified payments received.</p></article>
        <article class="card"><h2>Gender Allocation</h2><p>Male and Female hostels are separated during booking automatically.</p></article>
    </div>
</section>
<?php endif; ?>
<?php render_footer(); ?>

