<?php
require_once __DIR__ . '/config.php';
$user = require_login('maintenance');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? 'In Progress';
    db()->prepare('UPDATE maintenance_requests SET status=? WHERE id=? AND assigned_to=?')->execute([$status, (int)$_POST['request_id'], $user['id']]);
    $stmt = db()->prepare('SELECT student_id FROM maintenance_requests WHERE id=?');
    $stmt->execute([(int)$_POST['request_id']]);
    $request = $stmt->fetch();
    if ($request) {
        notify_user((int)$request['student_id'], 'Maintenance updated', 'Your maintenance request status is now ' . $status . '.');
    }
    flash('Task status updated.');
    redirect('staff.php');
}

$stmt = db()->prepare(
    "SELECT m.*, u.full_name student_name, u.phone
     FROM maintenance_requests m
     JOIN users u ON u.id=m.student_id
     WHERE m.assigned_to=?
     ORDER BY m.id DESC"
);
$stmt->execute([$user['id']]);

render_header('Maintenance Tasks');
?>
<section class="panel">
    <h1>Assigned Maintenance Requests</h1>
    <table>
        <tr><th>Student</th><th>Issue</th><th>Description</th><th>Status</th><th>Update</th></tr>
        <?php foreach ($stmt as $row): ?>
            <tr>
                <td><?= e($row['student_name']) ?><br><small><?= e($row['phone']) ?></small></td>
                <td><?= e($row['issue_type']) ?></td>
                <td><?= e($row['description']) ?></td>
                <td><?= e($row['status']) ?></td>
                <td>
                    <form method="post" class="inline">
                        <input type="hidden" name="request_id" value="<?= (int)$row['id'] ?>">
                        <select name="status">
                            <option>In Progress</option>
                            <option>Completed</option>
                            <option>Closed</option>
                        </select>
                        <button class="button">Update</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php render_footer(); ?>

