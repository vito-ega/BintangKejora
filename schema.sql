-- ==============================
-- DATABASE
-- ==============================
CREATE DATABASE IF NOT EXISTS bintangjaya
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE bintangjaya;

-- ==============================
-- ROLES
-- ==============================
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (id, name)
VALUES (1, 'admin'), (2, 'kasir')
ON DUPLICATE KEY UPDATE name = name;


-- ==============================
-- USERS
-- ==============================
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  fullname VARCHAR(150),
  role_id INT NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  remember_token VARCHAR(128),
  token_expiry DATETIME,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Seed admin
INSERT INTO users (username, password, fullname, role_id, is_active)
VALUES (
  'admin',
  '$2y$10$/oTlf65rdS9OwHhHMlB8HORH35VKFb.if67YgM.f5rD4B2HowABia',
  'Administrator',
  1,
  1
)
ON DUPLICATE KEY UPDATE username = username;

-- ==============================
-- CUSTOMERS
-- ==============================
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(50),
  email VARCHAR(150),
  address TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================
-- ITEMS
-- ==============================
CREATE TABLE items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  price INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- ==============================
-- CUSTOMER ITEM PRICE
-- ==============================
CREATE TABLE customer_item_price (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  item_id INT NOT NULL,
  price INT NOT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_customer_item (customer_id, item_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- ==============================
-- TRANSACTIONS
-- ==============================
CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_no VARCHAR(50) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  user_id INT NOT NULL,
  total INT NOT NULL DEFAULT 0,
  status ENUM('draft', 'final', 'void') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
);
