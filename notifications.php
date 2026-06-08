<?php
require_once __DIR__ . '/config.php';
$user = require_login();

if (isset($_POST['mark_read'])) {
    db()->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
    redirect('notifications.php');
}

$stmt = db()->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC');
$stmt->execute([$user['id']]);

render_header('Notifications');
?>
<section class="panel">
    <form method="post" class="right"><button class="button" name="mark_read">Mark All Read</button></form>
    <h1>Notifications</h1>
    <div class="list">
        <?php foreach ($stmt as $row): ?>
            <article class="notice <?= $row['is_read'] ? '' : 'unread' ?>">
                <h2><?= e($row['title']) ?></h2>
                <p><?= e($row['message']) ?></p>
                <small><?= e($row['created_at']) ?></small>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php render_footer(); ?>

