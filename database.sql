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

-- ---------------------------------------------------
-- Table: product_videos (Product video URLs)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_videos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `url`        VARCHAR(500) NOT NULL,
  `platform`   ENUM('youtube','facebook','tiktok') NOT NULL,
  `title`      VARCHAR(200) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pvideo_product` (`product_id`),
  CONSTRAINT `fk_pvideo_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: product_attributes (e.g., Size, Color, Material)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_attributes` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `name`       VARCHAR(100) NOT NULL,
  `values`     TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pattr_product` (`product_id`),
  CONSTRAINT `fk_pattr_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: product_variants (Combinations of attributes)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `sku`        VARCHAR(100) NOT NULL,
  `name`       VARCHAR(200) NOT NULL,
  `attributes` JSON NOT NULL,
  `price`      DECIMAL(12,2) NOT NULL,
  `sale_price` DECIMAL(12,2) DEFAULT NULL,
  `quantity`   INT NOT NULL DEFAULT 0,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_variant_sku` (`sku`),
  KEY `idx_pvar_product` (`product_id`),
  CONSTRAINT `fk_pvar_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: inventory_logs (Stock tracking and audit trail)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventory_logs` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`      INT UNSIGNED DEFAULT NULL,
  `variant_id`      INT UNSIGNED DEFAULT NULL,
  `quantity_change` INT NOT NULL,
  `reason`          VARCHAR(100) NOT NULL,
  `reference_id`    INT DEFAULT NULL,
  `notes`           TEXT DEFAULT NULL,
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inv_product` (`product_id`),
  KEY `idx_inv_variant` (`variant_id`),
  KEY `idx_inv_created` (`created_at`),
  CONSTRAINT `fk_inv_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_inv_variant` FOREIGN KEY (`variant_id`)
      REFERENCES `product_variants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: product_ratings (Customer reviews and ratings)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_ratings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `customer_id` INT UNSIGNED NOT NULL,
  `order_id`   INT UNSIGNED DEFAULT NULL,
  `rating`     TINYINT UNSIGNED NOT NULL,
  `title`      VARCHAR(200) DEFAULT NULL,
  `review`     TEXT DEFAULT NULL,
  `helpful_count` INT DEFAULT 0,
  `status`     ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prating_product` (`product_id`),
  KEY `idx_prating_customer` (`customer_id`),
  KEY `idx_prating_order` (`order_id`),
  KEY `idx_prating_status` (`status`),
  CONSTRAINT `fk_prating_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prating_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prating_order` FOREIGN KEY (`order_id`)
      REFERENCES `orders` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: coupon_codes (Discount coupons)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupon_codes` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(50) NOT NULL,
  `type`            ENUM('fixed','percentage') NOT NULL,
  `value`           DECIMAL(12,2) NOT NULL,
  `max_uses`        INT DEFAULT NULL,
  `used_count`      INT DEFAULT 0,
  `min_amount`      DECIMAL(12,2) DEFAULT NULL,
  `start_date`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expiry_date`     TIMESTAMP NULL,
  `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_coupon_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: order_coupons (Applied coupons to orders)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_coupons` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id`   INT UNSIGNED NOT NULL,
  `coupon_id`  INT UNSIGNED NOT NULL,
  `code`       VARCHAR(50) NOT NULL,
  `discount`   DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ocoupon_order` (`order_id`),
  KEY `idx_ocoupon_coupon` (`coupon_id`),
  CONSTRAINT `fk_ocoupon_order` FOREIGN KEY (`order_id`)
      REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ocoupon_coupon` FOREIGN KEY (`coupon_id`)
      REFERENCES `coupon_codes` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: product_discounts (Product-level discounts)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_discounts` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`    INT UNSIGNED NOT NULL,
  `type`          ENUM('fixed','percentage') NOT NULL,
  `value`         DECIMAL(12,2) NOT NULL,
  `min_quantity`  INT DEFAULT 1,
  `max_quantity`  INT DEFAULT NULL,
  `start_date`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expiry_date`   TIMESTAMP NULL,
  `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pdiscount_product` (`product_id`),
  CONSTRAINT `fk_pdiscount_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: analytics_daily_sales (Daily sales analytics)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `analytics_daily_sales` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `date`              DATE NOT NULL,
  `total_orders`      INT DEFAULT 0,
  `total_revenue`     DECIMAL(12,2) DEFAULT 0,
  `total_items_sold`  INT DEFAULT 0,
  `avg_order_value`   DECIMAL(12,2) DEFAULT 0,
  `created_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_analytics_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: analytics_product_performance (Product sales metrics)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `analytics_product_performance` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id`       INT UNSIGNED NOT NULL,
  `month`            VARCHAR(7) NOT NULL,
  `total_sold`       INT DEFAULT 0,
  `total_revenue`    DECIMAL(12,2) DEFAULT 0,
  `avg_rating`       DECIMAL(3,2) DEFAULT NULL,
  `view_count`       INT DEFAULT 0,
  `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_product_month` (`product_id`, `month`),
  KEY `idx_perf_product` (`product_id`),
  CONSTRAINT `fk_perf_product` FOREIGN KEY (`product_id`)
      REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- Table: analytics_customer_segments (Customer analytics)
-- ---------------------------------------------------
CREATE TABLE IF NOT EXISTS `analytics_customer_segments` (
  `id`                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id`           INT UNSIGNED NOT NULL,
  `total_orders`          INT DEFAULT 0,
  `total_spent`           DECIMAL(12,2) DEFAULT 0,
  `avg_order_value`       DECIMAL(12,2) DEFAULT 0,
  `last_order_date`       TIMESTAMP NULL,
  `customer_type`         ENUM('new','returning','loyal','inactive') NOT NULL DEFAULT 'new',
  `lifetime_value`        DECIMAL(12,2) DEFAULT 0,
  `created_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_segment_customer` (`customer_id`),
  KEY `idx_seg_type` (`customer_type`),
  CONSTRAINT `fk_seg_customer` FOREIGN KEY (`customer_id`)
      REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
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
