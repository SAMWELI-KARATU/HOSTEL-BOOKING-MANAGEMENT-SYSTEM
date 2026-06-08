<?php
require_once __DIR__ . '/config.php';
$user = require_login('student');

$stmt = db()->prepare(
    "SELECT p.*, b.invoice_number, h.name hostel_name, r.room_number
     FROM payments p
     JOIN bookings b ON b.id=p.booking_id
     JOIN rooms r ON r.id=b.room_id
     JOIN hostels h ON h.id=r.hostel_id
     WHERE p.student_id=? ORDER BY p.id DESC"
);
$stmt->execute([$user['id']]);

render_header('Payments');
?>
<section class="panel">
    <h1>Payment History</h1>
    <table>
        <tr><th>Invoice</th><th>Receipt</th><th>Hostel</th><th>Amount</th><th>Method</th><th>Status</th><th>Date</th></tr>
        <?php foreach ($stmt as $row): ?>
            <tr>
                <td><?= e($row['invoice_number']) ?></td>
                <td><?= e($row['receipt_number']) ?></td>
                <td><?= e($row['hostel_name']) ?> / <?= e($row['room_number']) ?></td>
                <td><?= number_format((float)$row['amount'], 2) ?></td>
                <td><?= e($row['method']) ?></td>
                <td><?= e($row['status']) ?></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php render_footer(); ?>

