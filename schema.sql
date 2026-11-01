
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('lost', 'found') NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    color VARCHAR(50) NOT NULL,
    location VARCHAR(150) NOT NULL,
    date DATE NOT NULL,
    reporter_name VARCHAR(150) DEFAULT 'Anonymous',
    reporter_email VARCHAR(255) NOT NULL,
    reporter_phone VARCHAR(50) DEFAULT 'N/A',
    description TEXT,
    status ENUM('active', 'resolved') DEFAULT 'active',
    image_url TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_status (type, status),
    INDEX idx_matching (category, color, location)
);

-- Seed Initial Data
INSERT INTO items (type, title, category, color, location, date, reporter_name, reporter_email, reporter_phone, description, status)
VALUES 
('lost', 'Scientific Calculator Casio FX-991EX', 'Calculator', 'Black', 'Library', '2026-10-28', 'Alex Rivera', 'alex.r@college.edu', '+1 (555) 234-5678', 'Left on the 2nd floor study desk.', 'active'),
('found', 'Casio ClassWiz FX-991EX Calculator', 'Calculator', 'Black', 'Library', '2026-10-28', 'Sarah Jenkins', 's.jenkins@college.edu', '+1 (555) 876-5432', 'Found on 2nd-floor study cubicle #14.', 'active');