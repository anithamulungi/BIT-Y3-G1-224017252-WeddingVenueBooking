CREATE DATABASE IF NOT EXISTS wedding_venue_booking
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE wedding_venue_booking;

CREATE TABLE IF NOT EXISTS customers (
  customer_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','manager') NOT NULL DEFAULT 'customer',
  groom_name VARCHAR(100),
  bride_name VARCHAR(100),
  phone VARCHAR(30),
  budget_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  budget_spent DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS venues (
  venue_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  location VARCHAR(180) NOT NULL,
  capacity SMALLINT UNSIGNED NOT NULL,
  price_per_day DECIMAL(10,2) NOT NULL,
  available TINYINT(1) NOT NULL DEFAULT 1,
  description TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_venues_available_location (available, location)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bookings (
  booking_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  venue_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  manager_id INT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_bookings_venue_date_status (venue_id, event_date, status),
  INDEX idx_bookings_customer (customer_id),
  CONSTRAINT fk_bookings_venue FOREIGN KEY (venue_id)
    REFERENCES venues(venue_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_customer FOREIGN KEY (customer_id)
    REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_bookings_manager FOREIGN KEY (manager_id)
    REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  payment_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(50) NOT NULL DEFAULT 'Mobile Money',
  status ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  paid_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_payments_booking (booking_id),
  CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id)
    REFERENCES bookings(booking_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reviews (
  review_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id INT UNSIGNED NOT NULL,
  venue_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  comment TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_reviews_customer (customer_id),
  INDEX idx_reviews_venue (venue_id),
  CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
  CONSTRAINT fk_reviews_customer FOREIGN KEY (customer_id)
    REFERENCES customers(customer_id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_reviews_venue FOREIGN KEY (venue_id)
    REFERENCES venues(venue_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
