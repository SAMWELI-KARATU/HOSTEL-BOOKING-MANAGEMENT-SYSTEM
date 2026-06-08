CREATE DATABASE IF NOT EXISTS hostel_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_booking;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS hostels;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','admin','maintenance') NOT NULL DEFAULT 'student',
    full_name VARCHAR(120) NOT NULL,
    gender ENUM('Male','Female') NULL,
    date_of_birth DATE NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    phone VARCHAR(40) NULL,
    department VARCHAR(120) NULL,
    academic_year VARCHAR(40) NULL,
    profile_photo VARCHAR(255) NULL,
    username VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hostels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    gender_category ENUM('Male','Female') NOT NULL,
    location VARCHAR(120) NULL,
    capacity INT NOT NULL DEFAULT 0,
    facilities TEXT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    room_number VARCHAR(40) NOT NULL,
    capacity INT NOT NULL,
    occupied_spaces INT NOT NULL DEFAULT 0,
    available_spaces INT NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    status ENUM('pending_payment','confirmed','approved','cancelled') NOT NULL DEFAULT 'pending_payment',
    invoice_number VARCHAR(80) NOT NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    method ENUM('Mobile Money','Debit Card','Credit Card','Bank Transfer') NOT NULL,
    status ENUM('pending','verified','failed') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(120) NOT NULL,
    receipt_number VARCHAR(120) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE maintenance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    assigned_to INT NULL,
    issue_type VARCHAR(120) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending','Assigned','In Progress','Completed','Closed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(120) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (role, full_name, gender, email, phone, department, academic_year, username, password_hash) VALUES
('admin', 'System Administrator', NULL, 'admin@hostel.test', '255700000001', NULL, NULL, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5z9l6LxL1yZCmoeGKS'),
('student', 'John Student', 'Male', 'john@student.test', '255700000002', 'Computer Science', '2025/2026', 'john@student.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5z9l6LxL1yZCmoeGKS'),
('student', 'Asha Student', 'Female', 'asha@student.test', '255700000003', 'Business Administration', '2025/2026', 'asha@student.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5z9l6LxL1yZCmoeGKS'),
('maintenance', 'Maintenance Staff', NULL, 'staff@hostel.test', '255700000004', NULL, NULL, 'staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5z9l6LxL1yZCmoeGKS');

INSERT INTO hostels (name, gender_category, location, capacity, facilities, description) VALUES
('Kilimanjaro Hostel', 'Male', 'North Campus', 80, 'Wi-Fi, Study room, Laundry, Security', 'Male hostel near lecture halls.'),
('Serengeti Hostel', 'Female', 'East Campus', 80, 'Wi-Fi, Common room, Laundry, Security', 'Female hostel with quiet study areas.');

INSERT INTO rooms (hostel_id, room_number, capacity, occupied_spaces, available_spaces, price) VALUES
(1, 'M-101', 4, 0, 4, 250000.00),
(1, 'M-102', 4, 0, 4, 250000.00),
(2, 'F-201', 4, 0, 4, 250000.00),
(2, 'F-202', 4, 0, 4, 250000.00);

