<?php
require_once __DIR__ . '/config.php';

if (current_user()) {
    redirect('dashboard.php');
}

render_header('Welcome');
?>
<section class="hero">
    <div>
        <p class="eyebrow">Educational Institution Housing</p>
        <h1>Book hostel rooms faster, fairly, and by allocation rules.</h1>
        <p>Students can register, find gender-eligible rooms, book, pay, and track maintenance requests while administrators manage the full hostel operation.</p>
        <div class="actions">
            <a class="button primary" href="register.php">Create Student Account</a>
            <a class="button" href="login.php">Login</a>
        </div>
    </div>
    <div class="stats">
        <span>Gender-based allocation</span>
        <span>Room occupancy tracking</span>
        <span>Payments and receipts</span>
        <span>Maintenance workflow</span>
    </div>
</section>
<?php render_footer(); ?>

