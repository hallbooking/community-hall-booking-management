CREATE DATABASE IF NOT EXISTS community_hall_booking;

USE community_hall_booking;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(25),
    role ENUM('customer','admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE halls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    location VARCHAR(200) NOT NULL,
    capacity INT NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    description TEXT,
    facilities VARCHAR(500),
    status ENUM('active','inactive') DEFAULT 'active'
);

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    hall_id INT NOT NULL,
    event_name VARCHAR(120) NOT NULL,
    event_date DATE NOT NULL,
    guest_count INT NOT NULL,
    notes TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM(
        'pending',
        'approved',
        'rejected',
        'cancelled',
        'completed'
    ) DEFAULT 'pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (hall_id)
        REFERENCES halls(id)
        ON DELETE RESTRICT,

    INDEX idx_hall_date_status
        (hall_id, event_date, status)
);

INSERT INTO halls
(name, location, capacity, price_per_day, description, facilities)
VALUES
(
    'Sri Ganesh Community Hall',
    'Vijayanagar, Mysuru',
    300,
    12000,
    'Hall for weddings and family functions',
    'Parking, Chairs, Stage'
),
(
    'Green Garden Function Hall',
    'Hebbal, Mysuru',
    180,
    8500,
    'Hall for birthday parties and meetings',
    'Garden, Tables, Kitchen'
),
(
    'Unity Community Centre',
    'Nazarbad, Mysuru',
    500,
    18000,
    'Large hall for weddings and conferences',
    'Stage, Sound System, Projector'
);