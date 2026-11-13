-- I Mobile.lk Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS imobile_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE imobile_db;

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_admin TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products Table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10, 2) NOT NULL,
    stock INT DEFAULT 0,
    image_url VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Order Items Table
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    UNIQUE KEY unique_order_product (order_id, product_id)
) ENGINE=InnoDB;

-- Sample Data

-- Categories
INSERT INTO categories (name, description) VALUES
('Smartphones', 'Latest mobile phones and smartphones'),
('Tablets', 'Tablets and iPad devices'),
('Accessories', 'Mobile phone accessories'),
('Laptops', 'Laptops and portable computers');

-- Admin User
INSERT INTO users (full_name, email, password, phone, is_admin) VALUES
('Admin User', 'admin@imobile.lk', '$2y$10$t6NqEr0.pFTaBHNXHo1iO.9dMhLmEjWLTdx0qPxKmw1F6WqaWQQxS', '0770000000', 1);

-- Sample Products
INSERT INTO products (name, description, category_id, price, stock, image_url, status) VALUES
('Samsung Galaxy S24', 'Latest flagship smartphone with advanced features', 1, 89999, 15, 'samsung-s24.jpg', 'active'),
('iPhone 15 Pro', 'Premium Apple smartphone with cutting-edge technology', 1, 139999, 8, 'iphone-15-pro.jpg', 'active'),
('OnePlus 12', 'Fast and powerful Android smartphone', 1, 64999, 20, 'oneplus-12.jpg', 'active'),
('iPad Air', 'Powerful tablet for work and entertainment', 2, 79999, 10, 'ipad-air.jpg', 'active'),
('Samsung Galaxy Buds', 'Wireless earbuds with noise cancellation', 3, 15999, 30, 'galaxy-buds.jpg', 'active'),
('USB-C Cable', 'High-quality USB-C charging cable', 3, 1999, 100, 'usb-c-cable.jpg', 'active'),
('MacBook Air M3', 'Lightweight and powerful laptop', 4, 199999, 5, 'macbook-air-m3.jpg', 'active'),
('Xiaomi 14', 'Mid-range flagship with great camera', 1, 49999, 25, 'xiaomi-14.jpg', 'active');

-- Grant privileges (uncomment if needed)
-- GRANT ALL PRIVILEGES ON imobile_db.* TO 'imobile_user'@'localhost' IDENTIFIED BY 'password';
-- FLUSH PRIVILEGES;
