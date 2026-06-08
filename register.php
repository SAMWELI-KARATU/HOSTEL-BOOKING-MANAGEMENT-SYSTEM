<?php
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $academicYear = trim($_POST['academic_year'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!in_array($gender, ['Male', 'Female'], true) || strlen($password) < 6) {
        flash('Please enter valid details. Password must be at least 6 characters.', 'error');
    } else {
        try {
            $stmt = db()->prepare(
                "INSERT INTO users (role, full_name, gender, email, phone, department, academic_year, username, password_hash)
                 VALUES ('student', ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $fullName,
                $gender,
                $email,
                $phone,
                $department,
                $academicYear,
                $email,
                password_hash($password, PASSWORD_DEFAULT),
            ]);
            notify_user((int)db()->lastInsertId(), 'Registration successful', 'Your student account has been created.');
            flash('Registration successful. You can now login.');
            redirect('login.php');
        } catch (PDOException $e) {
            flash('Email already exists or details are invalid.', 'error');
        }
    }
}

render_header('Register');
?>
<section class="panel">
    <h1>Student Registration</h1>
    <form method="post" class="form grid">
        <label>Full Name <input name="full_name" required></label>
        <label>Gender
            <select name="gender" required>
                <option value="">Select gender</option>
                <option>Male</option>
                <option>Female</option>
            </select>
        </label>
        <label>Email <input type="email" name="email" required></label>
        <label>Phone <input name="phone"></label>
        <label>Faculty/Department <input name="department"></label>
        <label>Academic Year <input name="academic_year" placeholder="2025/2026"></label>
        <label>Password <input type="password" name="password" required></label>
        <button class="button primary" type="submit">Register</button>
    </form>
</section>
<?php render_footer(); ?>

