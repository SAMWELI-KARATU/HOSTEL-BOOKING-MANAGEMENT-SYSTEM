<?php
require_once __DIR__ . '/config.php';
$user = require_login('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = db()->prepare('INSERT INTO maintenance_requests (student_id, issue_type, description, status) VALUES (?, ?, ?, "Pending")');
    $stmt->execute([$user['id'], $_POST['issue_type'] ?? 'Other', trim($_POST['description'] ?? '')]);
    flash('Maintenance request submitted.');
    redirect('maintenance.php');
}

$requests = db()->prepare(
    "SELECT m.*, u.full_name staff_name
     FROM maintenance_requests m
     LEFT JOIN users u ON u.id=m.assigned_to
     WHERE m.student_id=? ORDER BY m.id DESC"
);
$requests->execute([$user['id']]);

render_header('Maintenance');
?>
<section class="layout">
    <div class="panel">
        <h1>Submit Maintenance Request</h1>
        <form method="post" class="form">
            <label>Issue Type
                <select name="issue_type">
                    <option>Electrical faults</option>
                    <option>Water supply problems</option>
                    <option>Broken furniture</option>
                    <option>Internet connectivity issues</option>
                    <option>Security concerns</option>
                </select>
            </label>
            <label>Description <textarea name="description" required></textarea></label>
            <button class="button primary" type="submit">Submit Request</button>
        </form>
    </div>
    <div class="panel">
        <h2>Request History</h2>
        <table>
            <tr><th>Issue</th><th>Status</th><th>Assigned To</th><th>Date</th></tr>
            <?php foreach ($requests as $row): ?>
                <tr>
                    <td><?= e($row['issue_type']) ?></td>
                    <td><?= e($row['status']) ?></td>
                    <td><?= e($row['staff_name'] ?? 'Not assigned') ?></td>
                    <td><?= e($row['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php render_footer(); ?>

