<?php
require_once __DIR__ . '/config.php';
$user = require_login('student');

$stmt = db()->prepare('SELECT * FROM hostels WHERE gender_category = ? ORDER BY name');
$stmt->execute([$user['gender']]);
$hostels = $stmt->fetchAll();

render_header('Available Hostels');
?>
<section class="panel">
    <h1>Available <?= e($user['gender']) ?> Hostels</h1>
    <div class="cards">
        <?php foreach ($hostels as $hostel): ?>
            <article class="card">
                <h2><?= e($hostel['name']) ?></h2>
                <p><?= e($hostel['location']) ?></p>
                <p><?= e($hostel['description']) ?></p>
                <p><strong>Facilities:</strong> <?= e($hostel['facilities']) ?></p>
                <a class="button primary" href="book.php?hostel=<?= (int)$hostel['id'] ?>">View Rooms</a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>

