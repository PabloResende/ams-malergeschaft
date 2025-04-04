-- SQLs for the database

-- Users
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_hours INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    progress INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employees
CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    address TEXT,
    sex ENUM('male', 'female', 'other') NOT NULL,
    birth_date DATE NOT NULL,
    nationality VARCHAR(100),
    permission_type VARCHAR(100),
    email VARCHAR(255),
    ahv_number VARCHAR(50),
    phone VARCHAR(20),
    religion VARCHAR(50),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    role VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    about TEXT,
    profile_picture LONGBLOB,
    passport LONGBLOB,
    permission_photo_front LONGBLOB,
    permission_photo_back LONGBLOB,
    health_card_front LONGBLOB,
    health_card_back LONGBLOB,
    bank_card_front LONGBLOB,
    bank_card_back LONGBLOB,
    marriage_certificate LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Adicione índices conforme necessário
ALTER TABLE employees ADD INDEX idx_name (name);
ALTER TABLE employees ADD INDEX idx_role (role);

-- Clients
CREATE TABLE client (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address VARCHAR(255),
    about TEXT,
    phone VARCHAR(20),
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inventory
CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('material', 'equipment', 'rented') NOT NULL,
  name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- project_resources
CREATE TABLE project_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    resource_type ENUM('employee', 'inventory') NOT NULL,
    resource_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

