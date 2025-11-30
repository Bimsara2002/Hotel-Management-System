CREATE TABLE Sales (
    SaleId INT AUTO_INCREMENT PRIMARY KEY,
    OrderId int,
    SaleDate DATE,
    TotalAmount DECIMAL(12,2)
);

CREATE TABLE Revenue (
    RevenueId INT AUTO_INCREMENT PRIMARY KEY,
    MonthYear CHAR(7) NOT NULL UNIQUE, -- 'YYYY-MM'
    TotalIncome DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    TotalExpenses DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    NetRevenue DECIMAL(12,2) AS (TotalIncome - TotalExpenses) STORED,
    GeneratedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE LeaveRequests (
    LeaveId INT AUTO_INCREMENT PRIMARY KEY,
    StaffId INT,
    StartDate DATE,
    EndDate DATE,
    Reason VARCHAR(255),
    Status VARCHAR(20) DEFAULT 'Pending', -- Pending / Approved / Rejected
    RequestedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    Type VARCHAR(100)
);

CREATE TABLE Attendance (
    AttendanceId INT AUTO_INCREMENT PRIMARY KEY,
    StaffId INT,
    Date DATE,
    Status VARCHAR(20), -- Present / Absent / Late / On Leave
    CheckIn TIME,
    CheckOut TIME,
    OThours int
    
);

CREATE TABLE MonthlyOT (
    OTId INT AUTO_INCREMENT PRIMARY KEY,
    StaffId INT NOT NULL,
    Year INT NOT NULL,
    Month INT NOT NULL,
    TotalOT INT DEFAULT 0,
    GeneratedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_staff_month (StaffId, Year, Month)
);
select * from MonthlyOT;


CREATE TABLE SupplierPayment (
    PaymentId INT AUTO_INCREMENT PRIMARY KEY,
    SupplierName VARCHAR(100),
    Amount DECIMAL(12,2),
    PaymentDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PaymentStatus VARCHAR(30), -- pending/paid/not paid
    Status VARCHAR(20) DEFAULT 'Pending', -- Pending / Approved / Rejected
    PaymentMethod VARCHAR(100)
);
ALTER TABLE SupplierPayment 
ADD COLUMN supplier_id int Not null AFTER PaymentId;



INSERT INTO SupplierPayment (SupplierName, Amount, PaymentStatus, PaymentMethod)
VALUES
('AutoParts Lanka', 120000.00, 'pending', 'Bank Transfer'),
('SpeedTech Supplies', 89000.50, 'pending', 'Cheque'),
('MotorMax Distributors', 55000.75, 'not paid', 'Cash'),
('GearUp Traders', 76000.00, 'pending', 'Online Transfer'),
('RoadStar Imports', 133000.00, 'paid', 'Bank Transfer');


CREATE TABLE Performance (
    reviewId INT AUTO_INCREMENT PRIMARY KEY,
    staffId INT NOT NULL,
    reviewerId INT NOT NULL,       -- HR Manager or Supervisor
    reviewDate DATE NOT NULL,
    rating INT,                   -- 1-5 scale
    comments VARCHAR(500)
);

CREATE TABLE TrainingSession (
    sessionId INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(500),
    sessionDate DATE,
    startTime TIME,
    staffGroup VARCHAR(100)       -- e.g., "All staff", "Kitchen staff"
);

CREATE TABLE Resignation (
    resignationId INT AUTO_INCREMENT PRIMARY KEY,
    staffId INT NOT NULL,
    resignationDate DATE,
    reason VARCHAR(255),
    status varchar(100)
    
);

CREATE TABLE Payroll (
    PayrollID INT AUTO_INCREMENT PRIMARY KEY,
    StaffID INT NOT NULL,
    BaseSalary DECIMAL(10,2) NOT NULL,
    OT DECIMAL(10,2) DEFAULT 0.00,
    NetPay DECIMAL(10,2) GENERATED ALWAYS AS (BaseSalary + OT ) STORED,
    PaymentDate timestamp DEFAULT current_timestamp,
    Status VARCHAR(20) DEFAULT 'Pending'
   
); 
alter table Payroll add column Month
 varchar(100);
 
 select * from Items;
 
 CREATE TABLE Items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    unite varchar(30),
    price DECIMAL(10,2) NOT NULL,
    supplier_id INT
    
);

CREATE TABLE Stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    expiry_date date,
    reorder_level INT NOT NULL DEFAULT 5,  -- minimum quantity before reorder
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES Items(item_id) ON DELETE CASCADE
);

CREATE TABLE Suppliers (
    supplier_id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status varchar(100)
); 
ALTER TABLE Suppliers 
ADD COLUMN password VARCHAR(100) Not null AFTER email;


CREATE TABLE StockOrders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    item_name varchar(200),
    quantity INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Approved','Delivered','Cancelled') DEFAULT 'Pending',
    supplier_id INT NOT NULL
);

CREATE TABLE ItemRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status varchar(100) DEFAULT 'Pending'
);
ALTER TABLE ItemRequests 
ADD COLUMN item_name VARCHAR(100) AFTER item_id;
show tables;
CREATE TABLE NewItemRequests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name varchar(200),
    quantity INT NOT NULL,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status varchar(100) DEFAULT 'Pending'
);

create table KitchenJob(
	job_id int auto_increment primary key,
    order_id int ,
    chef_id int,
    status varchar(50)
);
ALTER TABLE KitchenJob ADD COLUMN orderGroup VARCHAR(100);


create table Chef(
	chef_id int auto_increment primary key,
    chef_name varchar(100),
    status  varchar(50)
 ); 
 
 
 create table KitchenRequest(
	request_id int auto_increment primary key,
    item_name varchar(100),
    quantity int,
    status varchar(50));
    
create table KitchenIssuse(
	issuse_id int auto_increment primary key,
    discription text	,
    status varchar(50)
);   
ALTER TABLE KitchenIssue 
ADD COLUMN maintenance_cost DECIMAL(10,2) DEFAULT 0;
ALTER TABLE KitchenIssue ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;



ALTER TABLE KitchenIssue CHANGE issuse_id issue_id INT AUTO_INCREMENT PRIMARY KEY;


CREATE TABLE Recipe (
    recipe_id INT AUTO_INCREMENT PRIMARY KEY,
    food_id INT NOT NULL,
    recipe_name VARCHAR(150) NOT NULL,
    description TEXT,
    ingredients TEXT,
    instructions TEXT,
    created_by INT
);


CREATE TABLE DeliveryJobs (
    delivery_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,                -- Related order
    delivery_status VARCHAR(50) DEFAULT 'Pending',   -- Pending / Assigned / Delivered / Cancelled
    driver_id INT DEFAULT NULL,           -- Assigned driver (nullable)
    payment_status VARCHAR(50) DEFAULT 'Unpaid',    -- Paid / Unpaid
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
alter table DeliveryJobs add column distance 
 double(10,2);
 ALTER TABLE DeliveryJobs ADD COLUMN orderGroup VARCHAR(50);


create table Driver(
driver_id int auto_increment primary key,
driver_name varchar(100)
);

create table VehicleIssue(
	issue_id int auto_increment primary key,
    vehicle_id int,
    status varchar(100),
    description text
);
 
 create table Vehicle(
	vehicle_id int auto_increment primary key,
    vehicle_number varchar(20),
    status varchar(100)
 );
 
 CREATE TABLE tables (
    table_id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(50),
    seats INT,
    availability VARCHAR(50) DEFAULT 'Available',
    customer_id int
);

alter table tables add column date 
TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
alter table tables add column reservationDate 
date;

CREATE TABLE RoomServiceRequests (
    requestId INT AUTO_INCREMENT PRIMARY KEY,
    roomNumber VARCHAR(10) NOT NULL,
    customerId INT NOT NULL,
    requestDetails TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending', -- Pending / Completed
    serviceNote TEXT,
    requestDate DATETIME DEFAULT CURRENT_TIMESTAMP
);


 select * from KitchenRequest;
 
 

INSERT INTO Resignation (staffId, resignationDate, reason, status) VALUES
(101, '2025-10-01', 'Pursuing higher studies', 'Pending'),
(102, '2025-09-25', 'Personal reasons', 'Pending'),
(103, '2025-09-30', 'Better job opportunity', 'Pending'),
(104, '2025-10-05', 'Relocation', 'Pending'),
(105, '2025-10-08', 'Health issues', 'Pending');

INSERT INTO Performance (staffId, reviewerId, reviewDate, rating, comments) VALUES
(2, 5, '2025-09-01', 5, 'Excellent leadership and decision-making.'),
(3, 5, '2025-09-02', 4, 'Strong management skills, slight delays in reporting.'),
(4, 5, '2025-09-03', 5, 'Manages restaurant operations efficiently.'),
(6, 5, '2025-09-04', 4, 'Accurate financial handling, improve time management.'),
(7, 5, '2025-09-05', 3, 'Inventory tracking is fair; needs better coordination.'),
(8, 5, '2025-09-06', 5, 'Outstanding organization of stock records.'),
(9, 5, '2025-09-07', 4, 'Good transport scheduling and team management.'),
(2, 5, '2025-10-01', 5, 'Owner continues to provide strong vision and leadership.'),
(3, 5, '2025-10-02', 4, 'General Manager performing well under pressure.'),
(4, 5, '2025-10-03', 5, 'Restaurant staff well-motivated and disciplined.'),
(6, 5, '2025-10-04', 4, 'Consistent accounting reports, some delays in monthly closings.'),
(7, 5, '2025-10-05', 3, 'Stock reordering process can be more proactive.'),
(8, 5, '2025-10-06', 5, 'Exemplary stock record keeping and reporting accuracy.'),
(9, 5, '2025-10-07', 4, 'Delivery coordination is smooth and punctual.');

INSERT INTO Items (item_name, description, unite, price, supplier_id) VALUES
('Safety Goggles', 'Standard clear safety glasses', 'Pair', 15.50, 101),
('Surgical Gloves', 'Latex-free sterile gloves, size M', 'Box (100 units)', 25.99, 102),
('Face Mask N95', 'Particulate respirator, N95 standard', 'Each', 2.75, 103),
('Cotton Swabs', 'Sterile cotton-tipped applicators', 'Box (500 units)', 9.80, 104),
('Hand Sanitizer', 'Alcohol-based gel, 500ml bottle', 'Bottle', 8.25, 101);


INSERT INTO Stock (item_id, quantity, expiry_date, reorder_level) VALUES
(1, 150, '2026-11-30', 50),
(2, 45, '2025-05-15', 20),
(3, 1000, '2027-01-01', 300),
(4, 15, '2026-03-20', 10),
(5, 5, '2025-09-01', 5);

select* from KitchenIssue

