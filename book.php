<?php
require_once __DIR__ . '/config.php';
$user = require_login('student');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomId = (int)($_POST['room_id'] ?? 0);
    $method = $_POST['method'] ?? 'Mobile Money';

    $roomStmt = db()->prepare(
        "SELECT r.*, h.gender_category, h.name hostel_name
         FROM rooms r JOIN hostels h ON h.id = r.hostel_id
         WHERE r.id = ? LIMIT 1"
    );
    $roomStmt->execute([$roomId]);
    $room = $roomStmt->fetch();

    if (!$room || $room['gender_category'] !== $user['gender']) {
        flash('You can only book hostels matching your gender.', 'error');
        redirect('hostels.php');
    }
    if ((int)$room['available_spaces'] <= 0) {
        flash('This room is fully occupied.', 'error');
        redirect('book.php?hostel=' . (int)$room['hostel_id']);
    }
    if (active_booking_for((int)$user['id'])) {
        flash('You already have an active booking.', 'error');
        redirect('dashboard.php');
    }

    db()->beginTransaction();
    try {
        $invoice = 'INV-' . date('YmdHis') . '-' . $user['id'];
        $stmt = db()->prepare('INSERT INTO bookings (student_id, room_id, status, invoice_number, amount) VALUES (?, ?, "confirmed", ?, ?)');
        $stmt->execute([$user['id'], $roomId, $invoice, $room['price']]);
        $bookingId = (int)db()->lastInsertId();

        $receipt = 'RCT-' . date('YmdHis') . '-' . $bookingId;
        $pay = db()->prepare('INSERT INTO payments (booking_id, student_id, amount, method, status, transaction_reference, receipt_number) VALUES (?, ?, ?, ?, "verified", ?, ?)');
        $pay->execute([$bookingId, $user['id'], $room['price'], $method, 'TXN' . random_int(100000, 999999), $receipt]);

        db()->prepare('UPDATE rooms SET occupied_spaces = occupied_spaces + 1, available_spaces = available_spaces - 1 WHERE id = ?')->execute([$roomId]);
        notify_user((int)$user['id'], 'Booking confirmed', 'Your booking for ' . $room['hostel_name'] . ' has been confirmed. Receipt: ' . $receipt);
        db()->commit();
        flash('Booking and payment completed successfully.');
        redirect('dashboard.php');
    } catch (Throwable $e) {
        db()->rollBack();
        flash('Booking failed. Please try again.', 'error');
    }
}

$hostelId = (int)($_GET['hostel'] ?? 0);
$hostelStmt = db()->prepare('SELECT * FROM hostels WHERE id=? AND gender_category=? LIMIT 1');
$hostelStmt->execute([$hostelId, $user['gender']]);
$hostel = $hostelStmt->fetch();
if (!$hostel) {
    flash('Hostel not found or not available for your gender.', 'error');
    redirect('hostels.php');
}

$rooms = db()->prepare('SELECT * FROM rooms WHERE hostel_id=? ORDER BY room_number');
$rooms->execute([$hostelId]);

render_header('Book Room');
?>
<section class="panel">
    <h1><?= e($hostel['name']) ?> Rooms</h1>
    <table>
        <tr><th>Room</th><th>Capacity</th><th>Available</th><th>Price</th><th>Action</th></tr>
        <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= e($room['room_number']) ?></td>
                <td><?= (int)$room['capacity'] ?></td>
                <td><?= (int)$room['available_spaces'] ?></td>
                <td><?= number_format((float)$room['price'], 2) ?></td>
                <td>
                    <?php if ((int)$room['available_spaces'] > 0): ?>
                        <form method="post" class="inline">
                            <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>">
                            <select name="method">
                                <option>Mobile Money</option>
                                <option>Debit Card</option>
                                <option>Credit Card</option>
                                <option>Bank Transfer</option>
                            </select>
                            <button class="button primary" type="submit">Pay & Book</button>
                        </form>
                    <?php else: ?>
                        <span class="badge danger">Full</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</section>
<?php render_footer(); ?>

