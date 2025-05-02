-- Restaurant database tables

-- User table for authentication and user management
CREATE TABLE `user` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(64) DEFAULT NULL,
    last_name VARCHAR(64) DEFAULT NULL,
    roles JSON NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurant information table
CREATE TABLE `restaurant` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    address VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    email VARCHAR(180) DEFAULT NULL,
    opening_hours TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Picture table for image gallery
CREATE TABLE `picture` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(64) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    position INT DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking table for table reservations
CREATE TABLE `booking` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    date DATETIME NOT NULL,
    guest_count INT NOT NULL,
    last_name VARCHAR(64) NOT NULL,
    first_name VARCHAR(64) DEFAULT NULL,
    email VARCHAR(180) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status VARCHAR(20) NOT NULL,
    notes TEXT DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    CONSTRAINT fk_booking_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu table for restaurant menus
CREATE TABLE `menu` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Category table for food categories
CREATE TABLE `category` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    position INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food table for menu items
CREATE TABLE `food` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu_Category join table for menu-category relationships
CREATE TABLE `menu_category` (
    menu_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (menu_id, category_id),
    CONSTRAINT fk_menu_category_menu FOREIGN KEY (menu_id) REFERENCES `menu` (id) ON DELETE CASCADE,
    CONSTRAINT fk_menu_category_category FOREIGN KEY (category_id) REFERENCES `category` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Food_Category join table for food-category relationships
CREATE TABLE `food_category` (
    food_id INT NOT NULL,
    category_id INT NOT NULL,
    PRIMARY KEY (food_id, category_id),
    CONSTRAINT fk_food_category_food FOREIGN KEY (food_id) REFERENCES `food` (id) ON DELETE CASCADE,
    CONSTRAINT fk_food_category_category FOREIGN KEY (category_id) REFERENCES `category` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Restaurant table (for real tables in the restaurant)
CREATE TABLE `restaurant_table` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number INT NOT NULL,
    seats INT NOT NULL,
    location VARCHAR(64) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Booking_Table join table for booking-table relationships
CREATE TABLE `booking_table` (
    booking_id INT NOT NULL,
    table_id INT NOT NULL,
    PRIMARY KEY (booking_id, table_id),
    CONSTRAINT fk_booking_table_booking FOREIGN KEY (booking_id) REFERENCES `booking` (id) ON DELETE CASCADE,
    CONSTRAINT fk_booking_table_table FOREIGN KEY (table_id) REFERENCES `restaurant_table` (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;