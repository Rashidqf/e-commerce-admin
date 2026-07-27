-- =====================================================
-- eCommerce Admin Panel - MySQL Database Schema
-- =====================================================
-- Engine: InnoDB (supports foreign keys)
-- Charset: utf8mb4 (full Unicode support)
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ---------------------------------------------------
-- Table: admins
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(100) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password`      VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(30) DEFAULT NULL,
  `address`       TEXT DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: categories
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`       VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image`       VARCHAR(255) DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: products
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id`      INT UNSIGNED NOT NULL,
  `name`             VARCHAR(200) NOT NULL,
  `sku`              VARCHAR(100) NOT NULL,
  `price`            DECIMAL(12,2) NOT NULL,
  `sale_price`       DECIMAL(12,2) DEFAULT NULL,
  `quantity`         INT NOT NULL DEFAULT 0,
  `short_description` VARCHAR(255) DEFAULT NULL,
  `long_description` TEXT DEFAULT NULL,
  `main_image`       VARCHAR(255) DEFAULT NULL,
  `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `view_count`       INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_sku` (`sku`),
  KEY `idx_product_category` (`category_id`),
  CONSTRAINT `fk_product_category` FOREIGN KEY (`category_id`)
      REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: product_images (gallery images)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image`      VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pimg_product` (`product_id`),
  CONSTRAINT `fk_pimg_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: customers
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `customers` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(150) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `phone`         VARCHAR(30) DEFAULT NULL,
  `address`       TEXT DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `password`      VARCHAR(255) DEFAULT NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_customer_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: orders
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`    INT UNSIGNED NOT NULL,
  `order_number`   VARCHAR(50) NOT NULL,
  `total`          DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery',
  `payment_status` ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  `order_status`   ENUM('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `shipping_address` TEXT NOT NULL,
  `created_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_order_number` (`order_number`),
  KEY `idx_order_customer` (`customer_id`),
  CONSTRAINT `fk_order_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: order_items
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `product_name` VARCHAR(200) NOT NULL,
  `product_image` VARCHAR(255) DEFAULT NULL,
  `quantity`   INT NOT NULL,
  `price`      DECIMAL(12,2) NOT NULL,
  `total`      DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_oitem_order` (`order_id`),
  KEY `idx_oitem_product` (`product_id`),
  CONSTRAINT `fk_oitem_order` FOREIGN KEY (`order_id`)
      REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oitem_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Default admin account
-- Email:    admin@example.com
-- Password: admin123
-- (password is hashed with PHP password_hash)
-- =====================================================
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('Super Admin', 'admin@example.com', '$2y$10$2tUAz918ltlLc2ePRRX7neJNv7i5i3X5kSwFtwQZdGOjo1VsgXhee');

-- =====================================================
-- Sample categories
-- =====================================================
INSERT INTO `categories` (`title`, `description`, `status`) VALUES
('Electronics', 'Electronic gadgets and devices', 'active'),
('Clothing', 'Apparel and fashion items', 'active'),
('Home & Kitchen', 'Home and kitchen appliances', 'active');

-- =====================================================
-- Sample products
-- =====================================================
INSERT INTO `products` (`category_id`, `name`, `sku`, `price`, `sale_price`, `quantity`, `short_description`, `long_description`, `status`) VALUES
(1, 'Wireless Mouse', 'WM-001', 25.00, 19.99, 100, 'Ergonomic wireless mouse', 'A high-precision wireless mouse with USB receiver and long battery life.', 'active'),
(1, 'Bluetooth Headphones', 'BH-002', 59.00, 49.00, 50, 'Noise-cancelling headphones', 'Over-ear Bluetooth headphones with active noise cancellation and 20-hour battery life.', 'active'),
(2, 'Cotton T-Shirt', 'TS-100', 15.00, NULL, 200, '100% cotton t-shirt', 'Comfortable cotton t-shirt available in multiple colors and sizes.', 'active'),
(3, 'Stainless Steel Kettle', 'SK-300', 35.00, 29.99, 30, '1.7L electric kettle', 'Fast-boiling stainless steel electric kettle with auto shut-off.', 'active');

-- =====================================================
-- Sample customers
-- =====================================================
INSERT INTO `customers` (`name`, `email`, `phone`, `address`) VALUES
('John Doe', 'john@example.com', '555-0101', '123 Main Street, Springfield'),
('Jane Smith', 'jane@example.com', '555-0102', '456 Oak Avenue, Riverdale');

-- =====================================================
-- Sample orders
-- =====================================================
INSERT INTO `orders` (`customer_id`, `order_number`, `total`, `payment_method`, `payment_status`, `order_status`, `shipping_address`) VALUES
(1, 'ORD-100001', 19.99, 'Cash on Delivery', 'pending', 'pending', '123 Main Street, Springfield'),
(2, 'ORD-100002', 98.00, 'Credit Card', 'paid', 'processing', '456 Oak Avenue, Riverdale');

-- =====================================================
-- Sample order items
-- =====================================================
INSERT INTO `order_items` (`order_id`, `product_id`, `product_name`, `product_image`, `quantity`, `price`, `total`) VALUES
(1, 1, 'Wireless Mouse', NULL, 1, 19.99, 19.99),
(2, 2, 'Bluetooth Headphones', NULL, 2, 49.00, 98.00);
