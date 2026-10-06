SET default_storage_engine=InnoDB;
DROP DATABASE IF EXISTS resturantDB;
CREATE DATABASE resturantDB;
USE resturantDB;

CREATE TABLE `customers` (
   customerID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   firstName VARCHAR(100) NOT NULL,
   lastName VARCHAR(100) NOT NULL,
   email VARCHAR(255) NOT NULL UNIQUE,
   phone VARCHAR(30)
);

CREATE TABLE `restaurantLocations` (
   locationID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   locationName VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE `restaurantTables` (
   tableID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   tableNumber INT NOT NULL UNIQUE,
   seatingCapacity TINYINT UNSIGNED NOT NULL,
   locationID INT NOT NULL,
   isActive BOOLEAN NOT NULL DEFAULT TRUE,
   FOREIGN KEY (locationID) REFERENCES `restaurantLocations`(locationID)
);

CREATE TABLE `bookings` (
   bookingID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
   customerID INT NOT NULL,
   tableID INT NOT NULL,
   bookingDate DATE NOT NULL,
   bookingTime TIME NOT NULL,
   partySize TINYINT UNSIGNED NOT NULL,
   bookingStatus ENUM('confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'confirmed',
   notes VARCHAR(500),
   createdAt TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
   INDEX idx_bookings_customer_date (customerID, bookingDate, bookingTime),
   INDEX idx_bookings_table_date (tableID, bookingDate, bookingTime),
   FOREIGN KEY (customerID) REFERENCES `customers`(customerID),
   FOREIGN KEY (tableID) REFERENCES `restaurantTables`(tableID)
);

INSERT INTO `customers` (customerID, firstName, lastName, email, phone) VALUES
   (NULL, 'Olivia', 'Bennett', 'olivia.bennett@example.com', '+45 20 11 22 33'),
   (NULL, 'Noah', 'Andersen', 'noah.andersen@example.com', '+45 21 34 56 78'),
   (NULL, 'Amelia', 'Carter', 'amelia.carter@example.com', '+45 22 45 67 89'),
   (NULL, 'Lucas', 'Nielsen', 'lucas.nielsen@example.com', '+45 23 56 78 90'),
   (NULL, 'Sofia', 'Hansen', 'sofia.hansen@example.com', '+45 24 67 89 01'),
   (NULL, 'Ethan', 'Miller', 'ethan.miller@example.com', '+45 25 78 90 12');

INSERT INTO `restaurantLocations` (locationID, locationName) VALUES
   (NULL, 'Window'),
   (NULL, 'Main dining room'),
   (NULL, 'Terrace'),
   (NULL, 'Private dining room');

INSERT INTO `restaurantTables` (tableID, tableNumber, seatingCapacity, locationID, isActive) VALUES
   (NULL, 1, 2, 1, TRUE),
   (NULL, 2, 2, 1, TRUE),
   (NULL, 3, 4, 2, TRUE),
   (NULL, 4, 4, 2, TRUE),
   (NULL, 5, 4, 3, TRUE),
   (NULL, 6, 6, 2, TRUE),
   (NULL, 7, 6, 4, TRUE),
   (NULL, 8, 8, 4, FALSE);

INSERT INTO `bookings` (bookingID, customerID, tableID, bookingDate, bookingTime, partySize, bookingStatus, notes) VALUES
   (NULL, 1, 3, '2026-10-03', '18:00:00', 3, 'confirmed', 'Anniversary dinner'),
   (NULL, 2, 1, '2026-10-03', '18:30:00', 2, 'confirmed', NULL),
   (NULL, 3, 6, '2026-10-03', '19:00:00', 5, 'confirmed', 'High chair requested'),
   (NULL, 1, 4, '2026-10-17', '19:30:00', 4, 'confirmed', NULL),
   (NULL, 4, 2, '2026-10-04', '17:30:00', 2, 'completed', NULL),
   (NULL, 5, 5, '2026-10-04', '20:00:00', 4, 'confirmed', 'Vegetarian menu'),
   (NULL, 6, 7, '2026-10-10', '18:00:00', 6, 'confirmed', 'Birthday dinner'),
   (NULL, 2, 3, '2026-10-18', '18:30:00', 4, 'confirmed', NULL),
   (NULL, 3, 1, '2026-10-24', '19:00:00', 2, 'cancelled', NULL);

-- Overview of all restaurant tables for the front-end.
SELECT t.tableID, t.tableNumber, t.seatingCapacity, l.locationName, t.isActive
FROM `restaurantTables` AS t
JOIN `restaurantLocations` AS l ON l.locationID = t.locationID
ORDER BY t.tableNumber;

-- All bookings for a customer, ordered by arrival date and time.
SET @customerID = 1;
SELECT b.bookingID, b.bookingDate, b.bookingTime, b.partySize, b.bookingStatus,
       t.tableNumber, c.firstName, c.lastName
FROM `bookings` AS b
JOIN `customers` AS c ON c.customerID = b.customerID
JOIN `restaurantTables` AS t ON t.tableID = b.tableID
WHERE b.customerID = @customerID
ORDER BY b.bookingDate, b.bookingTime;

-- Bookings and customer details for a table on a specific date.
SET @tableID = 1;
SET @bookingDate = '2026-10-03';
SELECT b.bookingID, b.bookingDate, b.bookingTime, b.partySize, b.bookingStatus,
       c.customerID, c.firstName, c.lastName, c.email, c.phone
FROM `bookings` AS b
JOIN `customers` AS c ON c.customerID = b.customerID
WHERE b.tableID = @tableID
  AND b.bookingDate = @bookingDate
ORDER BY b.bookingTime;


