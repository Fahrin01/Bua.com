-- =========================================================
-- BUA.COM Database
-- Maid/Bua hiring marketplace (Bangladesh) - Academic Project
-- =========================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

CREATE DATABASE IF NOT EXISTS bua_com CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bua_com;

-- =========================================================
-- 1. USERS (customer, agent, admin)
-- =========================================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer','agent','admin') NOT NULL DEFAULT 'customer',
    status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =========================================================
-- 2. AREAS
-- =========================================================
CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 3. SERVICES
-- =========================================================
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL UNIQUE,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- 4. MAIDS
-- =========================================================
CREATE TABLE maids (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    area_id INT NOT NULL,
    permanent_district VARCHAR(100) DEFAULT NULL,
    experience_years INT NOT NULL DEFAULT 0,
    expected_salary DECIMAL(10,2) NOT NULL DEFAULT 0,
    working_type ENUM('full_time','part_time','live_in','hourly') NOT NULL DEFAULT 'full_time',
    preferred_time VARCHAR(100) DEFAULT NULL,
    current_households INT NOT NULL DEFAULT 0,
    availability ENUM('available','busy','unavailable') NOT NULL DEFAULT 'available',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE RESTRICT,
    INDEX idx_availability (availability),
    INDEX idx_area (area_id),
    INDEX idx_salary (expected_salary),
    INDEX idx_experience (experience_years)
) ENGINE=InnoDB;

-- =========================================================
-- 5. MAID_SERVICES (many to many)
-- =========================================================
CREATE TABLE maid_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maid_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_maid_service (maid_id, service_id)
) ENGINE=InnoDB;

-- =========================================================
-- 6. MAID_VERIFICATION
-- =========================================================
CREATE TABLE maid_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maid_id INT NOT NULL UNIQUE,
    nid_verified TINYINT(1) NOT NULL DEFAULT 0,
    reference_verified TINYINT(1) NOT NULL DEFAULT 0,
    agent_verified TINYINT(1) NOT NULL DEFAULT 0,
    admin_verified TINYINT(1) NOT NULL DEFAULT 0,
    verification_status ENUM('PENDING','UNDER_REVIEW','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
    verification_notes TEXT DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    -- Sensitive fields, never publicly displayed
    nid_number VARCHAR(50) DEFAULT NULL,
    nid_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (verification_status)
) ENGINE=InnoDB;

-- =========================================================
-- 7. REFERENCES (private data - never public)
-- =========================================================
CREATE TABLE maid_references (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maid_id INT NOT NULL,
    reference_name VARCHAR(100) NOT NULL,
    reference_area VARCHAR(100) DEFAULT NULL,
    reference_phone VARCHAR(20) NOT NULL,
    relationship VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    verification_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 8. HIRING_REQUESTS
-- =========================================================
CREATE TABLE hiring_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    maid_id INT NOT NULL,
    service_id INT NOT NULL,
    preferred_time VARCHAR(100) DEFAULT NULL,
    expected_salary DECIMAL(10,2) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address VARCHAR(255) NOT NULL,
    customer_note TEXT DEFAULT NULL,
    status ENUM('PENDING','CONTACTING_MAID','MAID_CONSIDERING','ACCEPTED','REJECTED','HIRED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'PENDING',
    assigned_admin INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
    FOREIGN KEY (assigned_admin) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_customer (customer_id),
    INDEX idx_maid (maid_id)
) ENGINE=InnoDB;

-- =========================================================
-- 9. ADMIN_NOTES
-- =========================================================
CREATE TABLE admin_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    admin_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES hiring_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 10. REVIEWS
-- =========================================================
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    maid_id INT NOT NULL,
    request_id INT NOT NULL UNIQUE,
    overall_rating DECIMAL(2,1) NOT NULL,
    work_quality DECIMAL(2,1) NOT NULL,
    behavior_rating DECIMAL(2,1) NOT NULL,
    punctuality_rating DECIMAL(2,1) NOT NULL,
    reliability_rating DECIMAL(2,1) NOT NULL,
    comment TEXT DEFAULT NULL,
    status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES hiring_requests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =========================================================
-- 11. COMPLAINTS
-- =========================================================
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    maid_id INT NOT NULL,
    request_id INT DEFAULT NULL,
    reason ENUM('Misconduct','False information','Work abandonment','Payment dispute','Unprofessional behavior','Other') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('PENDING','UNDER_REVIEW','RESOLVED','REJECTED') NOT NULL DEFAULT 'PENDING',
    admin_note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES hiring_requests(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 12. FAVORITES
-- =========================================================
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    maid_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_fav (customer_id, maid_id)
) ENGINE=InnoDB;

-- =========================================================
-- 13. NOTIFICATIONS
-- =========================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) DEFAULT 'general',
    related_request_id INT DEFAULT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- =========================================================
-- 14. ACTIVITY_LOGS
-- =========================================================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =========================================================
-- 15. WORK_HISTORY
-- =========================================================
CREATE TABLE work_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maid_id INT NOT NULL,
    area VARCHAR(100) DEFAULT NULL,
    working_type VARCHAR(50) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    verification_status ENUM('PENDING','VERIFIED','REJECTED') NOT NULL DEFAULT 'PENDING',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (maid_id) REFERENCES maids(id) ON DELETE CASCADE
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================================
-- DEMO DATA
-- =========================================================

-- Admin password: Admin@123  | Agent password: Agent@123 | Customer password: Customer@123
-- (bcrypt hashes, verifiable with PHP's password_verify())
INSERT INTO users (name, email, phone, password, role, status) VALUES
('System Admin', 'admin@bua.com', '01700000000', '$2b$12$LXqs3aQ2TWNQ.Liowa4M1uFdfiX4TC8TOWDaPUejY4nuo2w90EWNS', 'admin', 'active'),
('Rafiq Ahmed', 'agent.rafiq@bua.com', '01711111111', '$2b$12$HJcD7nWKGEKq5JyDR4wd..Q3a6xTuELtchN4z0IpjOMJOpgjn24QK', 'agent', 'active'),
('Salma Khatun', 'agent.salma@bua.com', '01722222222', '$2b$12$HJcD7nWKGEKq5JyDR4wd..Q3a6xTuELtchN4z0IpjOMJOpgjn24QK', 'agent', 'active'),
('Tanvir Hasan', 'tanvir@example.com', '01811111111', '$2b$12$TRjPYH8R2kLBphkQSR4KS.Tarkes9AhfbfU6kAhR5oTSRl20tJFvy', 'customer', 'active'),
('Nusrat Jahan', 'nusrat@example.com', '01822222222', '$2b$12$TRjPYH8R2kLBphkQSR4KS.Tarkes9AhfbfU6kAhR5oTSRl20tJFvy', 'customer', 'active'),
('Kamal Uddin', 'kamal@example.com', '01833333333', '$2b$12$TRjPYH8R2kLBphkQSR4KS.Tarkes9AhfbfU6kAhR5oTSRl20tJFvy', 'customer', 'active'),
('Farida Yasmin', 'farida@example.com', '01844444444', '$2b$12$TRjPYH8R2kLBphkQSR4KS.Tarkes9AhfbfU6kAhR5oTSRl20tJFvy', 'customer', 'active'),
('Imran Molla', 'imran@example.com', '01855555555', '$2b$12$TRjPYH8R2kLBphkQSR4KS.Tarkes9AhfbfU6kAhR5oTSRl20tJFvy', 'customer', 'active');

INSERT INTO areas (area_name, status) VALUES
('Dhanmondi','active'),('Gulshan','active'),('Banani','active'),('Mirpur','active'),
('Uttara','active'),('Mohammadpur','active'),('Badda','active'),('Bashundhara','active'),
('Motijheel','active'),('Old Dhaka','active');

INSERT INTO services (service_name, status) VALUES
('House Cleaning','active'),('Cooking','active'),('Baby Care','active'),
('Elderly Care','active'),('Washing & Ironing','active'),('Full Household Management','active');

-- Maids (agent_id 2 = Rafiq, 3 = Salma)
INSERT INTO maids (agent_id, name, phone, photo, area_id, permanent_district, experience_years, expected_salary, working_type, preferred_time, current_households, availability, status) VALUES
(2,'Rahima Begum','01911111101',NULL,1,'Faridpur',5,6000,'full_time','Morning',1,'available','active'),
(2,'Amena Khatun','01911111102',NULL,2,'Barisal',3,7000,'part_time','Afternoon',2,'available','active'),
(2,'Rina Akter','01911111103',NULL,3,'Bogura',8,8500,'live_in','Anytime',0,'available','active'),
(2,'Shefali Begum','01911111104',NULL,4,'Kushtia',2,4500,'hourly','Morning',3,'busy','active'),
(2,'Momtaz Bibi','01911111105',NULL,5,'Rangpur',6,7500,'full_time','Morning',1,'available','active'),
(3,'Nasima Khatun','01911111106',NULL,6,'Comilla',4,6500,'part_time','Evening',2,'available','active'),
(3,'Halima Akter','01911111107',NULL,7,'Noakhali',10,9000,'live_in','Anytime',0,'available','active'),
(3,'Josna Begum','01911111108',NULL,8,'Jessore',1,4000,'part_time','Afternoon',4,'unavailable','active'),
(3,'Ruma Akter','01911111109',NULL,9,'Pabna',7,8000,'full_time','Morning',1,'available','active'),
(3,'Kulsum Bibi','01911111110',NULL,10,'Dinajpur',3,5500,'hourly','Morning',2,'available','active'),
(2,'Ayesha Siddika','01911111111',NULL,1,'Tangail',5,7000,'full_time','Morning',1,'available','active'),
(2,'Parul Begum','01911111112',NULL,2,'Sylhet',2,5000,'part_time','Afternoon',2,'available','active'),
(3,'Roksana Khatun','01911111113',NULL,3,'Khulna',9,9500,'live_in','Anytime',0,'available','active'),
(3,'Beauty Akter','01911111114',NULL,4,'Rajshahi',4,6200,'full_time','Morning',1,'busy','active'),
(2,'Shirin Begum','01911111115',NULL,5,'Mymensingh',6,7200,'part_time','Evening',2,'available','active');

INSERT INTO maid_services (maid_id, service_id) VALUES
(1,1),(1,2),(2,1),(2,5),(3,1),(3,2),(3,3),(4,1),(5,2),(5,6),
(6,1),(6,4),(7,3),(7,6),(8,1),(8,5),(9,2),(9,4),(10,1),(10,5),
(11,1),(11,2),(12,5),(13,6),(13,2),(14,1),(15,3),(15,4);

INSERT INTO maid_verification (maid_id, nid_verified, reference_verified, agent_verified, admin_verified, verification_status, verification_notes, verified_by, verified_at) VALUES
(1,1,1,1,1,'VERIFIED','All documents checked and confirmed.',1,NOW()),
(2,1,1,1,1,'VERIFIED','Reference contacted and confirmed.',1,NOW()),
(3,1,1,1,0,'UNDER_REVIEW','Awaiting final admin check.',NULL,NULL),
(4,1,0,1,0,'PENDING',NULL,NULL,NULL),
(5,1,1,1,1,'VERIFIED','Long-time trusted worker.',1,NOW()),
(6,1,1,1,1,'VERIFIED','Verified via phone and reference.',1,NOW()),
(7,1,1,1,1,'VERIFIED','10 years experience, well reviewed.',1,NOW()),
(8,0,0,1,0,'PENDING',NULL,NULL,NULL),
(9,1,1,1,1,'VERIFIED','Confirmed by two references.',1,NOW()),
(10,1,0,1,0,'UNDER_REVIEW','Reference verification in progress.',NULL,NULL),
(11,1,1,1,1,'VERIFIED','Approved after agent visit.',1,NOW()),
(12,1,0,1,0,'PENDING',NULL,NULL,NULL),
(13,1,1,1,1,'VERIFIED','Excellent track record.',1,NOW()),
(14,1,1,1,0,'UNDER_REVIEW',NULL,NULL,NULL),
(15,1,1,1,1,'VERIFIED','Confirmed identity and reference.',1,NOW());

INSERT INTO maid_references (maid_id, reference_name, reference_area, reference_phone, relationship, notes, verification_status) VALUES
(1,'Abdul Karim','Dhanmondi','01611111101','Previous Employer','Worked for 2 years','VERIFIED'),
(2,'Nasrin Sultana','Gulshan','01611111102','Previous Employer','Reliable and honest','VERIFIED'),
(5,'Jamal Uddin','Uttara','01611111105','Previous Employer','Good with cooking','VERIFIED');

INSERT INTO hiring_requests (customer_id, maid_id, service_id, preferred_time, expected_salary, start_date, customer_phone, customer_address, customer_note, status, assigned_admin) VALUES
(4,1,1,'Morning',6000,'2026-09-15','01811111111','House 12, Road 5, Dhanmondi','Need someone reliable for daily cleaning.','COMPLETED',1),
(5,2,5,'Afternoon',7000,'2026-09-20','01822222222','Flat 3B, Gulshan Avenue','Prefer experienced worker.','HIRED',1),
(6,5,2,'Morning',7500,'2026-09-25','01833333333','House 8, Uttara Sector 7','Cooking for a family of 4.','ACCEPTED',1),
(7,9,4,'Morning',8000,'2026-10-01','01844444444','House 21, Motijheel','Elderly care needed, patient person preferred.','CONTACTING_MAID',1),
(4,7,3,'Anytime',9000,'2026-10-05','01811111111','House 12, Road 5, Dhanmondi','Baby care for infant.','PENDING',NULL);

INSERT INTO admin_notes (request_id, admin_id, note) VALUES
(1,1,'Called maid on 05 September. She agreed to start immediately. Confirmed salary ৳6,000.'),
(2,1,'Called maid on 06 September. She is available from next Monday. Expected salary ৳7,000.'),
(3,1,'Contacted maid, she is considering the offer, will confirm within 2 days.'),
(4,1,'Attempting to reach maid, phone was busy, will retry.');

INSERT INTO reviews (customer_id, maid_id, request_id, overall_rating, work_quality, behavior_rating, punctuality_rating, reliability_rating, comment, status) VALUES
(4,1,1,4.8,5.0,4.5,5.0,4.5,'Excellent work, very punctual and clean. Highly recommended!','visible');

INSERT INTO complaints (customer_id, maid_id, request_id, reason, description, status, admin_note) VALUES
(6,8,NULL,'False information','Maid profile listed experience that did not match what was described during the call.','UNDER_REVIEW','Reviewing with agent who registered this maid.');

INSERT INTO favorites (customer_id, maid_id) VALUES
(4,3),(4,7),(5,9),(6,1),(7,13);

INSERT INTO notifications (user_id, title, message, type, related_request_id, is_read) VALUES
(4,'Request Submitted','Your hiring request has been submitted. Our admin will contact the maid.','request_submitted',5,0),
(4,'Hiring Completed','Your hiring has been completed. You can now submit a review.','request_completed',1,1),
(5,'Hiring Confirmed','Your hiring request has been confirmed.','request_hired',2,0),
(6,'Maid Accepted','The maid is available for your requested work.','request_accepted',3,0),
(7,'Contacting Maid','Our admin is currently contacting the maid regarding your request.','request_contacting',4,0),
(1,'New Hiring Request','A new hiring request has been submitted by Imran Molla.','new_request',5,0),
(1,'New Complaint','A new complaint has been submitted regarding Josna Begum.','new_complaint',NULL,0),
(2,'Maid Verification Pending','Your registered maid Shefali Begum is awaiting admin verification.','verification_pending',NULL,0);

INSERT INTO activity_logs (user_id, action, description) VALUES
(1,'LOGIN','Admin logged in'),
(2,'ADD_MAID','Agent added maid Rahima Begum'),
(4,'HIRING_REQUEST','Customer submitted hiring request for maid #1'),
(1,'UPDATE_STATUS','Admin updated request #1 status to COMPLETED');

INSERT INTO work_history (maid_id, area, working_type, start_date, end_date, notes, verification_status) VALUES
(1,'Dhanmondi','full_time','2023-01-01','2024-12-01','Worked as house help for a family of 4.','VERIFIED'),
(7,'Noakhali','live_in','2018-01-01','2024-06-01','Long term live-in household worker.','VERIFIED');
