-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,  -- hashed
    full_name VARCHAR(100),
    role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Birds (flock)
CREATE TABLE birds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    breed VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    age_weeks INT DEFAULT 0,
    pen_location VARCHAR(50),
    purchase_date DATE,
    cost_price DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Feed inventory
CREATE TABLE feed_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feed_type VARCHAR(50) NOT NULL,
    quantity_kg DECIMAL(10,2) NOT NULL,
    unit_price DECIMAL(10,2),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Feed consumption log
CREATE TABLE feed_consumption (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_id INT,                -- optional, if consumption is per batch
    feed_type VARCHAR(50),
    quantity_kg DECIMAL(10,2),
    consumption_date DATE,
    notes TEXT,
    FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE SET NULL
);

-- Health records
CREATE TABLE health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_id INT,
    record_date DATE,
    event_type VARCHAR(50),      -- e.g., vaccination, treatment, mortality
    description TEXT,
    cost DECIMAL(10,2),
    FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE CASCADE
);

-- Egg production
CREATE TABLE egg_production (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bird_id INT,                -- or breed/pen level
    production_date DATE,
    egg_count INT,
    broken_count INT DEFAULT 0,
    FOREIGN KEY (bird_id) REFERENCES birds(id) ON DELETE CASCADE
);

-- Sales
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_date DATE,
    item_type ENUM('eggs','birds') NOT NULL,
    quantity INT,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    customer_name VARCHAR(100),
    notes TEXT
);

-- Expenses
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE,
    category VARCHAR(50),       -- feed, medicine, labour, etc.
    description TEXT,
    amount DECIMAL(10,2),
    payment_method VARCHAR(20)
);

ALTER TABLE users ADD COLUMN email VARCHAR(100) UNIQUE AFTER username;