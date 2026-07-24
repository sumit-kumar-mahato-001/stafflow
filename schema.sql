CREATE DATABASE IF NOT EXISTS ems_database CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ems_database;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    join_date DATE NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Active',
    profile_pic VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (name, email, password) VALUES 
('Admin Manager', 'fighter@gmail.com', '$2y$10$J1lU.eCIpJUL7tZsXUNFUe5Vvz0VfxlxZrUZGXQYDlVuI1uVqYEnS')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO departments (name) VALUES 
('Engineering'),
('Marketing'),
('Human Resources'),
('Finance'),
('Sales'),
('Operations')
ON DUPLICATE KEY UPDATE id=id;

INSERT INTO employees (name, email, phone, department, position, salary, join_date, status, profile_pic) VALUES
('Sarah Johnson', 'sarah.j@company.com', '+1 (555) 234-5678', 'Engineering', 'Senior Frontend Developer', 95000.00, '2022-06-15', 'Active', NULL),
('Michael Chen', 'michael.c@company.com', '+1 (555) 987-6543', 'Marketing', 'Marketing Campaign Manager', 78000.00, '2023-01-10', 'On Leave', NULL),
('Emily Davis', 'emily.d@company.com', '+1 (555) 456-7890', 'Human Resources', 'HR Director', 88000.00, '2021-11-01', 'Active', NULL),
('James Wilson', 'james.w@company.com', '+1 (555) 321-0987', 'Finance', 'Financial Analyst', 72000.00, '2023-05-18', 'Active', NULL),
('Lisa Park', 'lisa.p@company.com', '+1 (555) 654-3210', 'Engineering', 'DevOps Engineer', 105000.00, '2022-09-01', 'Active', NULL),
('David Kim', 'david.k@company.com', '+1 (555) 789-0123', 'Sales', 'Enterprise Account Executive', 65000.00, '2024-02-15', 'Active', NULL)
ON DUPLICATE KEY UPDATE id=id;
