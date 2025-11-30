-- Create database
CREATE DATABASE IF NOT EXISTS OMIK;
USE OMIK;

-- Create Staff table
CREATE TABLE Staff (
    StaffId INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(50),
    LastName VARCHAR(50),
    AccountNumber INT,
    JobRole VARCHAR(100),
    OtRate DOUBLE,
    Email VARCHAR(100) UNIQUE,
    Status VARCHAR(20),
    Password VARCHAR(255) NOT NULL
);
alter table Staff add column BasicSalary
 double(10,2);
 alter table Staff add column chef_id
 int;

select * from staff;

-- Insert sample data
INSERT INTO Staff (FirstName, LastName, AccountNumber, JobRole, OtRate, Email, Status,password)
VALUES
('Admin', 'Perera', 123456, 'Manager', 1500.00, 'admin@omik.com', 'Active','1234');

INSERT INTO Staff (FirstName, LastName, AccountNumber, JobRole, OtRate, Email, Status, Password) VALUES
('Nimal', 'Perera', 1001, 'Owner', 0.00, 'owner@omik.com', 'Active', 'owner123'),
('Suresh', 'Fernando', 1002, 'General Manager', 50.00, 'generalmanager@omik.com', 'Active', 'gm123'),
('Chathura', 'Jayasinghe', 1003, 'Restaurant Manager', 45.00, 'restaurantmanager@omik.com', 'Active', 'rm123'),
('Kavindu', 'Silva', 1004, 'HR Manager', 40.00, 'hrmanager@omik.com', 'Active', 'hr123'),
('Anushka', 'Bandara', 1005, 'Accountant', 38.00, 'accountant@omik.com', 'Active', 'acc123'),
('Tharindu', 'Weerasinghe', 1006, 'Inventory Manager', 35.00, 'inventorymanager@omik.com', 'Active', 'inv123'),
('Dilshan', 'Fernando', 1007, 'Stock Keeper', 30.00, 'stockkeeper@omik.com', 'Active', 'stock123'),
('Pasindu', 'Rathnayake', 1008, 'Transport Manager', 35.00, 'transportmanager@omik.com', 'Active', 'tm123'),
('Nadeesha', 'Senanayake', 1009, 'Chef', 32.00, 'chef@omik.com', 'Active', 'chef123'),
('Isuru', 'Perera', 1010, 'Cashier', 25.00, 'cashier@omik.com', 'Active', 'cash123'),
('Sanduni', 'Fernando', 1011, 'Receptionist', 22.00, 'receptionist@omik.com', 'Active', 'recep123'),
('Manoj', 'Kumara', 1012, 'Room Keeper', 20.00, 'roomkeeper@omik.com', 'Active', 'room123'),
('Ravindu', 'Lakshan', 1013, 'Supervisor', 28.00, 'supervisor@omik.com', 'Active', 'sup123'),
('Kasun', 'Ekanayake', 1014, 'Delivery Boy', 18.00, 'deliveryboy@omik.com', 'Active', 'del123');


select * from Staff;

CREATE TABLE Customer (
    CustomerId INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(100) NOT NULL,
    Email VARCHAR(100) NOT NULL UNIQUE,
    Contact VARCHAR(15),
    Address TEXT,
    Password VARCHAR(255) NOT NULL
);
INSERT INTO Customer (Name, Email, Contact, Address, Password)
VALUES ('Danuja', 'dana@example.com', '0771234567', '123 Main Street, Colombo', '123');

create table food(
foodId int auto_increment primary key,
foodName varchar(100),
discription varchar(500),
price double,
status varchar(20),
image VARCHAR(255)  );
alter table food add column category
 varchar(100);
 alter table food add column meal_type
 varchar(100);
CREATE TABLE food_size (
    sizeId INT AUTO_INCREMENT PRIMARY KEY,
    foodId INT,
    size VARCHAR(20),     -- Small, Medium, Large, XL
    price DOUBLE
);



INSERT INTO food_size (foodId, size, price) VALUES
(1, 'Small', 800),
(1, 'Medium', 1000),
(1, 'Large', 1200),
(1, 'XL', 1500);

create table room(
roomId int auto_increment primary key,
ACorNot varchar(20),
status varchar(20),
type varchar(20),
price double,
vip varchar(30),
image varchar(500));

INSERT INTO room (ACorNot, status, type, price, vip,image) VALUES
('AC', 'Available', 'Single', 3500.00, 'Normal',"image/room1"),
('Non-AC', 'Booked', 'Double', 2500.00, 'Normal',"image/room2"),
('AC', 'Available', 'Deluxe', 5500.00, 'VIP',"image/room3");

UPDATE room SET image = 'image/room1.jpeg' WHERE roomId = 1;
UPDATE room SET image = 'image/room2.jpg' WHERE roomId = 2;
UPDATE room SET image = 'image/room3.jpg' WHERE roomId = 3;

CREATE TABLE room_booking (
  bookingId INT AUTO_INCREMENT PRIMARY KEY,
  customerId INT NOT NULL,
  roomId INT NOT NULL,
  checkIn DATE NOT NULL,
  checkOut DATE NOT NULL,
  numGuests INT DEFAULT 1,
  offerId INT DEFAULT NULL,
  totalAmount DOUBLE NOT NULL,
  paymentStatus varchar(100) DEFAULT 'Pending',
  bookingStatus varchar(100) DEFAULT 'Available',
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  CONSTRAINT fk_customer FOREIGN KEY (customerId) REFERENCES customer(customerId) ON DELETE CASCADE,
  CONSTRAINT fk_room FOREIGN KEY (roomId) REFERENCES room(roomId) ON DELETE CASCADE,
  CONSTRAINT fk_offer FOREIGN KEY (offerId) REFERENCES room_offers(offerId) ON DELETE SET NULL
);
CREATE TABLE room_offers (
    offerId INT AUTO_INCREMENT PRIMARY KEY,
    roomType VARCHAR(50) NOT NULL,      -- The room type this offer applies to (e.g., Deluxe, Standard)
    title VARCHAR(100) NOT NULL,        -- Offer title (e.g., Weekend Deal)
    description TEXT NOT NULL,          -- Details about the offer
    discount_percent DOUBLE,            -- Discount percentage (e.g., 20 for 20% off)
    valid_from DATE,                    -- Offer start date
    valid_to DATE                       -- Offer end date
);
ALTER TABLE room_offers
ADD COLUMN status ENUM('Active','Inactive') DEFAULT 'Active';

ALTER TABLE room_offers
ADD COLUMN createdBy INT DEFAULT NULL;

select* from room_booking ;

create table feedBack(
	feedbackId int auto_increment primary key,
    discription varchar(250),
    reply varchar(250),
    fdate DATETIME DEFAULT CURRENT_TIMESTAMP 
);
UPDATE feedBack 
SET reply = "Thank you for your feedback! We really appreciate your input and will work on improving our service."
WHERE feedbackId = 1;

ALTER TABLE feedBack 
ADD COLUMN rating INT DEFAULT 0;


CREATE TABLE customerOrders (
    orderId INT AUTO_INCREMENT PRIMARY KEY,
    customerId INT NOT NULL,
    foodId INT NOT NULL,
    quantity INT NOT NULL,
    amount DOUBLE NOT NULL,
    orderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    paymentType varchar(100),
    paymentStatus varchar(100),
    type VARCHAR(50) DEFAULT 'Dine-in',        -- Dine-in / Takeaway / Delivery
    deliveryStatus VARCHAR(20) DEFAULT 'Pending',   
    status VARCHAR(20) DEFAULT 'Pending'     -- Pending / Completed / Cancelled

);
ALTER TABLE customerOrders 
ADD COLUMN orderGroup VARCHAR(100) AFTER status;
show tables;
ALTER TABLE customerOrders
ADD COLUMN sizeId INT DEFAULT 0;

drop table customerOrders;


CREATE TABLE cart (
    cartId INT AUTO_INCREMENT PRIMARY KEY,
    foodId INT NOT NULL,
    foodName VARCHAR(100) NOT NULL,
    foodImage VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DOUBLE NOT NULL,
    totalPrice DOUBLE AS (quantity * price) STORED,
    sessionId VARCHAR(100) default NULL,  -- for guest users
    userId INT DEFAULT NULL,          -- for logged-in users
    addedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE IF EXISTS billing;

CREATE TABLE billing (
    billingId INT AUTO_INCREMENT PRIMARY KEY,
    orderGroup VARCHAR(100) NOT NULL,         -- ✅ groups all items together
    customerId INT NOT NULL,                  -- reference to the user
    fullName VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    address VARCHAR(500) NOT NULL,
    paymentType VARCHAR(50) NOT NULL,         -- Cash on Delivery / Card / Online
    paymentStatus VARCHAR(50) DEFAULT 'Pending',
    totalAmount DOUBLE NOT NULL,              -- ✅ total for all items
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
);






ALTER TABLE billing 
ADD COLUMN orderGroup VARCHAR(100) AFTER orderId,
ADD COLUMN totalAmount DOUBLE DEFAULT 0 AFTER paymentStatus;
ALTER TABLE billing 
MODIFY COLUMN orderId INT NULL;

select *  from customerOrders;
INSERT INTO room_offers (roomType, title, description, discount_percent, valid_from, valid_to, status, createdBy) VALUES
('Single', 'Weekend Special', 'Book a Single room this weekend and get 15% off.', 15, '2025-10-20', '2025-10-26', 'Active', 1),
('Double', 'Couple Deal', 'Stay in a Double room with your partner and enjoy 20% discount.', 20, '2025-10-15', '2025-11-15', 'Active', 1),
('Suite', 'Luxury Stay', 'Book a Suite and receive complimentary breakfast plus 25% off.', 25, '2025-10-01', '2025-12-31', 'Active', 1),
('Standard', 'Early Bird Offer', 'Book in advance for a Standard room and get 10% off.', 10, '2025-10-10', '2025-11-30', 'Active', 1),
('VIP', 'VIP Exclusive', 'VIP rooms at 30% discount for a limited time.', 30, '2025-10-20', '2025-10-31', 'Active', 1);
