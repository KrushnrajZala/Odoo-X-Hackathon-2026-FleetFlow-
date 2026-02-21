-- Create database
CREATE DATABASE IF NOT EXISTS fleetflow;
USE fleetflow;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    profile_image VARCHAR(255),
    role ENUM('admin', 'driver', 'passenger') DEFAULT 'passenger',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Vehicles table (create before driver_details due to foreign key)
CREATE TABLE vehicles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_name VARCHAR(100) NOT NULL,
    license_plate VARCHAR(20) UNIQUE NOT NULL,
    vehicle_type ENUM('truck', 'van', 'car', 'bike') DEFAULT 'car',
    max_capacity_kg DECIMAL(10,2) NOT NULL,
    current_odometer INT DEFAULT 0,
    fuel_type ENUM('petrol', 'diesel', 'electric', 'hybrid') DEFAULT 'petrol',
    status ENUM('available', 'on_trip', 'in_shop', 'retired') DEFAULT 'available',
    last_maintenance DATE,
    next_maintenance DATE,
    insurance_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_plate (license_plate),
    INDEX idx_status (status),
    INDEX idx_type (vehicle_type)
);

-- Driver details table
CREATE TABLE driver_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    license_number VARCHAR(50) UNIQUE,
    license_expiry DATE,
    vehicle_type ENUM('car', 'van', 'truck', 'bike') DEFAULT 'car',
    experience_years INT DEFAULT 0,
    safety_score DECIMAL(3,2) DEFAULT 5.00,
    total_trips INT DEFAULT 0,
    total_earnings DECIMAL(10,2) DEFAULT 0,
    current_status ENUM('available', 'on_trip', 'off_duty', 'suspended') DEFAULT 'off_duty',
    assigned_vehicle_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    INDEX idx_license (license_number),
    INDEX idx_status (current_status)
);

-- Trips table
CREATE TABLE trips (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_number VARCHAR(20) UNIQUE NOT NULL,
    passenger_id INT NOT NULL,
    driver_id INT,
    vehicle_id INT,
    pickup_location TEXT NOT NULL,
    dropoff_location TEXT NOT NULL,
    pickup_lat DECIMAL(10,8),
    pickup_lng DECIMAL(11,8),
    dropoff_lat DECIMAL(10,8),
    dropoff_lng DECIMAL(11,8),
    cargo_weight_kg DECIMAL(10,2),
    distance_km DECIMAL(10,2),
    estimated_fare DECIMAL(10,2),
    actual_fare DECIMAL(10,2),
    status ENUM('pending', 'accepted', 'started', 'completed', 'cancelled') DEFAULT 'pending',
    cancellation_reason TEXT,
    payment_method ENUM('cash', 'card', 'wallet') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    accepted_at TIMESTAMP NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_driver (driver_id),
    INDEX idx_passenger (passenger_id),
    INDEX idx_created (created_at),
    INDEX idx_trip_number (trip_number)
);

-- Trip tracking table
CREATE TABLE trip_tracking (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    driver_id INT NOT NULL,
    driver_lat DECIMAL(10,8) NOT NULL,
    driver_lng DECIMAL(11,8) NOT NULL,
    speed DECIMAL(5,2) DEFAULT 0,
    heading INT DEFAULT 0,
    accuracy DECIMAL(5,2) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_trip (trip_id),
    INDEX idx_driver (driver_id),
    INDEX idx_updated (updated_at)
);

-- Maintenance logs
CREATE TABLE maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    cost DECIMAL(10,2) NOT NULL,
    service_date DATE NOT NULL,
    next_service_date DATE,
    odometer_reading INT,
    status ENUM('scheduled', 'in_progress', 'completed') DEFAULT 'scheduled',
    performed_by VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_service_date (service_date)
);

-- Fuel logs
CREATE TABLE fuel_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vehicle_id INT NOT NULL,
    trip_id INT,
    liters DECIMAL(10,2) NOT NULL,
    cost DECIMAL(10,2) NOT NULL,
    price_per_liter DECIMAL(10,2),
    odometer_reading INT,
    fuel_date DATE NOT NULL,
    fuel_type ENUM('petrol', 'diesel', 'electric') DEFAULT 'diesel',
    station_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL,
    INDEX idx_vehicle (vehicle_id),
    INDEX idx_fuel_date (fuel_date)
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'wallet') NOT NULL,
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX idx_trip (trip_id),
    INDEX idx_status (payment_status)
);

-- Driver ratings
CREATE TABLE driver_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trip_id INT NOT NULL,
    driver_id INT NOT NULL,
    passenger_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip_rating (trip_id),
    INDEX idx_driver (driver_id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_read (is_read)
);

-- Settings table
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample data with hashed passwords
INSERT INTO users (username, email, password, full_name, phone, role, status) VALUES
('admin', 'admin@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '+1234567890', 'admin', 'active'),
('john_driver', 'john.driver@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Smith', '+1234567891', 'driver', 'active'),
('sarah_driver', 'sarah.driver@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Johnson', '+1234567892', 'driver', 'active'),
('mike_driver', 'mike.driver@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Wilson', '+1234567893', 'driver', 'active'),
('emma_driver', 'emma.driver@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Emma Brown', '+1234567894', 'driver', 'active'),
('david_driver', 'david.driver@fleetflow.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'David Lee', '+1234567895', 'driver', 'suspended'),
('alice_passenger', 'alice@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Alice Wonder', '+1234567896', 'passenger', 'active'),
('bob_passenger', 'bob@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Bob Marley', '+1234567897', 'passenger', 'active'),
('charlie_passenger', 'charlie@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Charlie Brown', '+1234567898', 'passenger', 'active'),
('diana_passenger', 'diana@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Diana Prince', '+1234567899', 'passenger', 'active');

-- Insert vehicles first
INSERT INTO vehicles (vehicle_name, license_plate, vehicle_type, max_capacity_kg, current_odometer, fuel_type, status, last_maintenance, next_maintenance, insurance_expiry) VALUES
('Toyota Camry', 'ABC-1234', 'car', 400, 45890, 'petrol', 'available', '2024-01-15', '2024-04-15', '2024-12-31'),
('Ford Transit', 'XYZ-5678', 'van', 1200, 67230, 'diesel', 'available', '2024-02-01', '2024-05-01', '2024-11-30'),
('Volvo Truck', 'TRK-9012', 'truck', 5000, 128450, 'diesel', 'on_trip', '2024-01-20', '2024-04-20', '2024-10-15'),
('Honda City', 'CITY-3456', 'car', 350, 23450, 'petrol', 'available', '2024-02-10', '2024-05-10', '2024-09-30'),
('Yamaha FZ', 'BIKE-7890', 'bike', 150, 12340, 'petrol', 'available', '2024-02-15', '2024-05-15', '2024-08-31'),
('Tata Ace', 'ACE-2345', 'truck', 850, 78900, 'diesel', 'in_shop', '2024-01-05', '2024-04-05', '2024-07-31'),
('Mahindra Bolero', 'BOL-6789', 'van', 800, 56780, 'diesel', 'available', '2024-02-20', '2024-05-20', '2024-12-31'),
('Hyundai i20', 'I20-9012', 'car', 380, 34560, 'petrol', 'retired', '2023-12-01', '2024-03-01', '2024-06-30');

-- Insert driver details (after vehicles)
INSERT INTO driver_details (user_id, license_number, license_expiry, vehicle_type, experience_years, safety_score, total_trips, total_earnings, current_status, assigned_vehicle_id) VALUES
(2, 'DL123456789', '2025-12-31', 'car', 5, 4.8, 1250, 45750.00, 'available', 1),
(3, 'DL987654321', '2024-10-15', 'van', 3, 4.9, 850, 38900.00, 'available', 2),
(4, 'DL456789123', '2025-06-30', 'truck', 7, 4.7, 2100, 89200.00, 'off_duty', 3),
(5, 'DL789123456', '2024-08-20', 'bike', 2, 4.9, 450, 15600.00, 'available', 5),
(6, 'DL321654987', '2023-12-31', 'car', 1, 3.5, 120, 3400.00, 'suspended', NULL);

-- Insert trips
INSERT INTO trips (trip_number, passenger_id, driver_id, vehicle_id, pickup_location, dropoff_location, 
                  pickup_lat, pickup_lng, dropoff_lat, dropoff_lng, 
                  distance_km, estimated_fare, actual_fare, status, 
                  created_at, accepted_at, started_at, completed_at,
                  payment_method, payment_status) VALUES
('TRIP001', 7, 2, 1, '123 Main St, New York, NY', '456 Park Ave, New York, NY',
 40.7128, -74.0060, 40.7614, -73.9776, 5.2, 18.50, 18.50, 'completed',
 '2024-02-15 09:30:00', '2024-02-15 09:32:00', '2024-02-15 09:35:00', '2024-02-15 09:55:00',
 'card', 'paid'),
('TRIP002', 8, 2, 1, '789 Broadway, New York, NY', '321 5th Ave, New York, NY',
 40.7590, -73.9845, 40.7489, -73.9850, 3.1, 12.50, 12.50, 'completed',
 '2024-02-15 14:20:00', '2024-02-15 14:22:00', '2024-02-15 14:25:00', '2024-02-15 14:40:00',
 'cash', 'paid'),
('TRIP003', 9, 3, 2, '100 Central Park West, New York, NY', '200 W 57th St, New York, NY',
 40.7697, -73.9763, 40.7658, -73.9805, 2.8, 15.00, 15.00, 'completed',
 '2024-02-16 10:15:00', '2024-02-16 10:17:00', '2024-02-16 10:20:00', '2024-02-16 10:35:00',
 'card', 'paid'),
('TRIP004', 10, 4, 3, '150 Columbus Ave, New York, NY', '300 Madison Ave, New York, NY',
 40.7725, -73.9802, 40.7485, -73.9857, 6.8, 45.00, 45.00, 'completed',
 '2024-02-16 13:45:00', '2024-02-16 13:47:00', '2024-02-16 13:50:00', '2024-02-16 14:25:00',
 'card', 'paid'),
('TRIP005', 7, 5, 5, '50 E 42nd St, New York, NY', '75 Rockefeller Plaza, New York, NY',
 40.7577, -73.9787, 40.7597, -73.9795, 1.2, 8.00, 8.00, 'completed',
 '2024-02-17 08:30:00', '2024-02-17 08:32:00', '2024-02-17 08:35:00', '2024-02-17 08:45:00',
 'cash', 'paid'),
('TRIP006', 8, NULL, NULL, '500 W 42nd St, New York, NY', '600 8th Ave, New York, NY',
 40.7605, -73.9954, 40.7598, -73.9912, 2.5, 11.50, NULL, 'pending',
 '2024-02-18 09:00:00', NULL, NULL, NULL,
 'card', 'pending'),
('TRIP007', 9, 2, 1, '700 7th Ave, New York, NY', '800 Broadway, New York, NY',
 40.7619, -73.9833, 40.7598, -73.9890, 3.8, 14.50, NULL, 'accepted',
 '2024-02-18 10:30:00', '2024-02-18 10:32:00', NULL, NULL,
 'cash', 'pending'),
('TRIP008', 10, 3, 2, '900 Park Ave, New York, NY', '1000 Lexington Ave, New York, NY',
 40.7755, -73.9625, 40.7785, -73.9598, 4.2, 16.00, NULL, 'started',
 '2024-02-18 11:00:00', '2024-02-18 11:02:00', '2024-02-18 11:05:00', NULL,
 'card', 'pending'),
('TRIP009', 7, 4, 3, '1100 1st Ave, New York, NY', '1200 2nd Ave, New York, NY',
 40.7652, -73.9598, 40.7692, -73.9558, 3.5, 22.00, NULL, 'cancelled',
 '2024-02-17 15:20:00', '2024-02-17 15:22:00', NULL, NULL,
 'card', 'pending'),
('TRIP010', 8, 2, 1, '1300 3rd Ave, New York, NY', '1400 4th Ave, New York, NY',
 40.7725, -73.9698, 40.7755, -73.9658, 2.8, 12.50, 12.50, 'completed',
 '2024-02-14 09:30:00', '2024-02-14 09:32:00', '2024-02-14 09:35:00', '2024-02-14 09:52:00',
 'cash', 'paid'),
('TRIP011', 9, 3, 2, '1500 5th Ave, New York, NY', '1600 6th Ave, New York, NY',
 40.7698, -73.9858, 40.7728, -73.9898, 3.2, 16.00, 16.00, 'completed',
 '2024-02-14 14:15:00', '2024-02-14 14:17:00', '2024-02-14 14:20:00', '2024-02-14 14:42:00',
 'card', 'paid'),
('TRIP012', 10, 5, 5, '1700 7th Ave, New York, NY', '1800 8th Ave, New York, NY',
 40.7658, -73.9928, 40.7688, -73.9958, 1.8, 9.50, 9.50, 'completed',
 '2024-02-13 11:45:00', '2024-02-13 11:47:00', '2024-02-13 11:50:00', '2024-02-13 12:05:00',
 'cash', 'paid');

-- Insert trip tracking data
INSERT INTO trip_tracking (trip_id, driver_id, driver_lat, driver_lng, speed, heading, accuracy) VALUES
(8, 3, 40.7750, -73.9620, 35.5, 90, 5.0),
(7, 2, 40.7615, -73.9830, 0, 0, 3.0);

-- Insert maintenance logs
INSERT INTO maintenance_logs (vehicle_id, service_type, description, cost, service_date, next_service_date, odometer_reading, status) VALUES
(1, 'Oil Change', 'Regular oil change and filter replacement', 89.99, '2024-01-15', '2024-04-15', 45500, 'completed'),
(2, 'Brake Service', 'Front brake pad replacement', 199.50, '2024-02-01', '2024-08-01', 67000, 'completed'),
(3, 'Engine Tune-up', 'Full engine diagnostic and tune-up', 349.99, '2024-01-20', '2024-07-20', 128000, 'completed'),
(6, 'Transmission Service', 'Transmission fluid change and filter', 299.99, '2024-01-05', '2024-07-05', 78500, 'completed'),
(6, 'Tire Rotation', 'Tire rotation and balance', 49.99, '2024-02-15', '2024-05-15', 78900, 'in_progress');

-- Insert fuel logs
INSERT INTO fuel_logs (vehicle_id, trip_id, liters, cost, price_per_liter, odometer_reading, fuel_date, fuel_type, station_name) VALUES
(1, 1, 45.2, 67.80, 1.50, 45950, '2024-02-15', 'petrol', 'Shell Station'),
(1, 2, 38.5, 57.75, 1.50, 46120, '2024-02-16', 'petrol', 'Exxon Station'),
(2, 3, 55.0, 104.50, 1.90, 67350, '2024-02-16', 'diesel', 'BP Station'),
(3, 4, 120.5, 228.95, 1.90, 128800, '2024-02-17', 'diesel', 'Chevron Station'),
(5, 5, 12.8, 19.20, 1.50, 12400, '2024-02-17', 'petrol', 'Mobil Station');

-- Insert payments
INSERT INTO payments (trip_id, amount, payment_method, payment_status, transaction_id, payment_date) VALUES
(1, 18.50, 'card', 'completed', 'TXN123456789', '2024-02-15 09:55:00'),
(2, 12.50, 'cash', 'completed', NULL, '2024-02-15 14:40:00'),
(3, 15.00, 'card', 'completed', 'TXN123456790', '2024-02-16 10:35:00'),
(4, 45.00, 'card', 'completed', 'TXN123456791', '2024-02-16 14:25:00'),
(5, 8.00, 'cash', 'completed', NULL, '2024-02-17 08:45:00'),
(10, 12.50, 'cash', 'completed', NULL, '2024-02-14 09:52:00'),
(11, 16.00, 'card', 'completed', 'TXN123456792', '2024-02-14 14:42:00'),
(12, 9.50, 'cash', 'completed', NULL, '2024-02-13 12:05:00');

-- Insert driver ratings
INSERT INTO driver_ratings (trip_id, driver_id, passenger_id, rating, review) VALUES
(1, 2, 7, 5, 'Excellent driver, very professional and punctual!'),
(2, 2, 8, 4, 'Good ride, clean car, friendly driver'),
(3, 3, 9, 5, 'Best ride ever! Very comfortable and safe driving'),
(4, 4, 10, 4, 'Good service, but arrived 5 minutes late'),
(5, 5, 7, 5, 'Great bike ride, fast and safe'),
(10, 2, 8, 5, 'Very polite and helpful driver'),
(11, 3, 9, 4, 'Good ride, but could improve route knowledge'),
(12, 5, 10, 5, 'Excellent service, highly recommended!');

-- Insert notifications
INSERT INTO notifications (user_id, title, message, type, is_read) VALUES
(2, 'New Trip Assigned', 'You have been assigned a new trip #TRIP007', 'info', false),
(3, 'Trip Started', 'Your trip #TRIP008 has been started', 'success', false),
(7, 'Trip Completed', 'Your trip #TRIP001 has been completed successfully', 'success', true),
(8, 'Payment Received', 'Payment of $12.50 has been processed for trip #TRIP002', 'success', true),
(9, 'Driver on the way', 'Your driver is on the way to pickup location', 'info', false),
(4, 'Maintenance Due', 'Vehicle #6 requires maintenance soon', 'warning', false);

-- Insert settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'FleetFlow', 'Website name'),
('site_email', 'support@fleetflow.com', 'Support email address'),
('site_phone', '+1-800-FLEETFLOW', 'Support phone number'),
('base_fare', '5.00', 'Base fare for rides'),
('per_km_rate_car', '2.50', 'Per kilometer rate for cars'),
('per_km_rate_van', '3.50', 'Per kilometer rate for vans'),
('per_km_rate_truck', '5.00', 'Per kilometer rate for trucks'),
('per_km_rate_bike', '1.50', 'Per kilometer rate for bikes'),
('currency_symbol', '$', 'Currency symbol'),
('tax_rate', '8.875', 'Tax rate percentage'),
('driver_commission', '80', 'Driver commission percentage'),
('min_ride_distance', '1', 'Minimum ride distance in km'),
('max_ride_distance', '500', 'Maximum ride distance in km'),
('support_hours', '24/7', 'Customer support hours'),
('company_address', '123 Fleet Street, New York, NY 10001', 'Company physical address'),
('google_maps_api_key', 'YOUR_API_KEY_HERE', 'Google Maps API key for mapping services');

-- Create triggers
DELIMITER //

CREATE TRIGGER before_insert_trips
BEFORE INSERT ON trips
FOR EACH ROW
BEGIN
    DECLARE next_id INT;
    SELECT AUTO_INCREMENT INTO next_id 
    FROM information_schema.tables 
    WHERE table_name = 'trips' AND table_schema = DATABASE();
    SET NEW.trip_number = CONCAT('TRIP', LPAD(next_id, 5, '0'));
END;//

CREATE TRIGGER after_trip_completed
AFTER UPDATE ON trips
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        UPDATE driver_details 
        SET total_trips = total_trips + 1,
            total_earnings = total_earnings + NEW.actual_fare
        WHERE user_id = NEW.driver_id;
        
        UPDATE vehicles 
        SET current_odometer = current_odometer + (NEW.distance_km * 1000)
        WHERE id = NEW.vehicle_id;
        
        IF NOT EXISTS (SELECT 1 FROM payments WHERE trip_id = NEW.id) THEN
            INSERT INTO payments (trip_id, amount, payment_method, payment_status, payment_date)
            VALUES (NEW.id, NEW.actual_fare, NEW.payment_method, 'completed', NOW());
        END IF;
    END IF;
END;//

CREATE TRIGGER after_trip_started
AFTER UPDATE ON trips
FOR EACH ROW
BEGIN
    IF NEW.status = 'started' AND OLD.status != 'started' THEN
        UPDATE driver_details 
        SET current_status = 'on_trip'
        WHERE user_id = NEW.driver_id;
        
        UPDATE vehicles 
        SET status = 'on_trip'
        WHERE id = NEW.vehicle_id;
    END IF;
END;//

CREATE TRIGGER after_trip_ended
AFTER UPDATE ON trips
FOR EACH ROW
BEGIN
    IF NEW.status IN ('completed', 'cancelled') AND OLD.status NOT IN ('completed', 'cancelled') THEN
        UPDATE driver_details 
        SET current_status = 'available'
        WHERE user_id = NEW.driver_id;
        
        UPDATE vehicles 
        SET status = 'available'
        WHERE id = NEW.vehicle_id;
    END IF;
END;//

DELIMITER ;

-- Create views
CREATE VIEW vw_active_trips AS
SELECT 
    t.id,
    t.trip_number,
    t.pickup_location,
    t.dropoff_location,
    t.distance_km,
    t.estimated_fare,
    t.status,
    t.created_at,
    p.full_name AS passenger_name,
    p.phone AS passenger_phone,
    d.full_name AS driver_name,
    d.phone AS driver_phone,
    v.vehicle_name,
    v.license_plate
FROM trips t
JOIN users p ON t.passenger_id = p.id
LEFT JOIN users d ON t.driver_id = d.id
LEFT JOIN vehicles v ON t.vehicle_id = v.id
WHERE t.status IN ('pending', 'accepted', 'started');

CREATE VIEW vw_driver_performance AS
SELECT 
    u.id AS driver_id,
    u.full_name AS driver_name,
    d.vehicle_type,
    d.total_trips,
    d.total_earnings,
    d.safety_score,
    COUNT(DISTINCT r.id) AS total_ratings,
    AVG(r.rating) AS avg_rating,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_trips,
    SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_trips
FROM users u
JOIN driver_details d ON u.id = d.user_id
LEFT JOIN trips t ON u.id = t.driver_id
LEFT JOIN driver_ratings r ON u.id = r.driver_id
WHERE u.role = 'driver'
GROUP BY u.id;

CREATE VIEW vw_vehicle_maintenance AS
SELECT 
    v.id,
    v.vehicle_name,
    v.license_plate,
    v.current_odometer,
    v.last_maintenance,
    v.next_maintenance,
    v.insurance_expiry,
    DATEDIFF(v.next_maintenance, CURDATE()) AS days_to_maintenance,
    CASE 
        WHEN DATEDIFF(v.next_maintenance, CURDATE()) <= 7 THEN 'critical'
        WHEN DATEDIFF(v.next_maintenance, CURDATE()) <= 30 THEN 'warning'
        ELSE 'good'
    END AS maintenance_status
FROM vehicles v
WHERE v.status != 'retired';