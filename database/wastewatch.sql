 
-- WasteWatch: Community Waste Collection & Reporting System
-- SDG 11: Sustainable Cities and Communities
-- Database schema + seed data
-- Import via phpMyAdmin, or: mysql -u root -p < wastewatch.sql

DROP DATABASE IF EXISTS wastewatch;
CREATE DATABASE wastewatch CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE wastewatch;

-- Table: zones
-- Collection zones a resident belongs to and a schedule serves

CREATE TABLE zones (
    zone_id INT AUTO_INCREMENT PRIMARY KEY,
    zone_name VARCHAR(100) NOT NULL UNIQUE,
    zone_description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- Table: users
-- Both residents and administrators

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL UNIQUE,
    role ENUM('admin','resident') NOT NULL DEFAULT 'resident',
    zone_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table: collection_schedules
-- Weekly collection routes per zone, managed by admin

CREATE TABLE collection_schedules (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    zone_id INT NOT NULL,
    day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    collection_time TIME NOT NULL,
    waste_type ENUM('General','Recyclable','Organic') NOT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_schedule_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
 
-- Table: reports
-- Resident-submitted issue reports (missed pickup, dumping, etc.)
 
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    zone_id INT NOT NULL,
    report_type ENUM('Missed Collection','Illegal Dumping','Overflowing Bin','Other') NOT NULL,
    description TEXT NOT NULL,
    location_details VARCHAR(255) DEFAULT NULL,
    status ENUM('Pending','In Progress','Resolved') NOT NULL DEFAULT 'Pending',
    admin_notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_reports_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_reports_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Table: pickup_requests
-- Resident requests for special/bulky waste pickup

CREATE TABLE pickup_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    zone_id INT NOT NULL,
    waste_category ENUM('Bulky Items','Electronic Waste','Garden Waste','Construction Debris','Other') NOT NULL,
    estimated_quantity VARCHAR(50) DEFAULT NULL,
    preferred_date DATE NOT NULL,
    status ENUM('Pending','Scheduled','Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pickup_user FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pickup_zone FOREIGN KEY (zone_id) REFERENCES zones(zone_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Seed data: zones
-- Each zone covers a division with specific districts and villages

INSERT INTO zones (zone_name, zone_description) VALUES
('Zone A - Nakawa',   'Nakawa Division: covers Nakawa, Naguru, Ntinda, Kyambogo, Bugolobi, Luzira and Banda villages'),
('Zone B - Kawempe',  'Kawempe Division: covers Kawempe, Mpererwe, Kazo, Bwaise, Makerere, Mulago and Kyebando villages'),
('Zone C - Makindye', 'Makindye Division: covers Makindye, Kibuli, Nsambya, Gogonya, Katwe, Kibuye and Ggaba villages'),
('Zone D - Lubaga',   'Lubaga Division: covers Lubaga, Namirembe, Lungujja, Kasubi, Nateete, Mutundwe and Busega villages'),
('Zone E - Central',  'Central Division: covers Kampala Central, Nakasero, Kololo, Old Kampala, Makerere Hill and Wandegeya villages');

-- Seed data: users
-- Passwords below are bcrypt hashes generated with PHP password_hash().
-- Admin   -> username: admin     password: Admin@2026
-- Resident-> username: testuser  password: Resident@2026
-- (See README.txt / System Documentation Section 7 for credentials)
 
INSERT INTO users (full_name, username, email, password_hash, phone, role, zone_id) VALUES
('Administrator', 'admin', 'admin@wastewatch.local',
 '$2b$10$NBEbLFvEYkul3d8uXK3XSuthQK3ihCd400vRokhSFP0V/21wPRVha', '0700000000', 'admin', NULL),
('Resident', 'testuser', 'resident@wastewatch.local',
 '$2b$10$f0w.tKl/qRvBVbUyR3X6GeEBhN0u8qbO15TQJPCTTLeh0K9DRVkUe', '0700000001', 'resident', 1);

-- Seed data: collection_schedules

INSERT INTO collection_schedules (zone_id, day_of_week, collection_time, waste_type, notes) VALUES
(1, 'Monday',    '07:00:00', 'General',    'Front-street pickup, bins out by 6:45am'),
(1, 'Thursday',  '07:00:00', 'Recyclable', 'Separate plastics and paper'),
(2, 'Tuesday',   '08:00:00', 'General',    'Community collection point at market'),
(2, 'Friday',    '08:00:00', 'Organic',    'Garden and food waste only'),
(3, 'Wednesday', '07:30:00', 'General',    'Roadside collection'),
(3, 'Saturday',  '09:00:00', 'Recyclable', 'Drop-off bins at estate gate'),
(4, 'Monday',    '08:30:00', 'General',    'Kaveera-free zone; no plastic bags'),
(4, 'Friday',    '08:30:00', 'Organic',    'Food and garden waste collection'),
(5, 'Wednesday', '06:30:00', 'General',    'Early morning CBD collection'),
(5, 'Saturday',  '07:00:00', 'Recyclable', 'Drop-off at Central Police Station yard');

-- Seed data: sample reports (for demo/testing only)

INSERT INTO reports (user_id, zone_id, report_type, description, location_details, status, created_at) VALUES
(2, 1, 'Missed Collection', 'Bins on Spring Close were not collected on the scheduled Monday route.', 'Spring Close, near junction with 5th Street', 'Pending', NOW() - INTERVAL 6 DAY),
(2, 1, 'Illegal Dumping', 'Construction debris dumped on the vacant plot behind the primary school.', 'Behind Nakawa Primary School', 'In Progress', NOW() - INTERVAL 3 DAY),
(2, 1, 'Overflowing Bin', 'Communal skip at the market entrance is overflowing and attracting pests.', 'Nakawa Market, main entrance', 'Resolved', NOW() - INTERVAL 10 DAY);

-- Seed data: sample pickup request

INSERT INTO pickup_requests (user_id, zone_id, waste_category, estimated_quantity, preferred_date, status) VALUES
(2, 1, 'Bulky Items', 'Old sofa and mattress', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Pending');
