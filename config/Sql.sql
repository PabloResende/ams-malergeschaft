Pensou por 6 segundos


sql
-- Users (sem alterações)
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clients (agora com active e loyalty_points)
CREATE TABLE client (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  address VARCHAR(255),
  about TEXT,
  phone VARCHAR(20),
  profile_picture VARCHAR(255),
  active TINYINT(1) NOT NULL DEFAULT 1,
  loyalty_points INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Projects (substitui client_name por client_id + FK)
CREATE TABLE projects (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  client_id INT NULL,
  location VARCHAR(255),
  description TEXT,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  total_hours INT DEFAULT 0,
  budget DECIMAL(10,2) DEFAULT 0.00,
  employee_count INT DEFAULT 0,
  status ENUM('pending','in_progress','completed') DEFAULT 'pending',
  progress INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id)
    REFERENCES client(id)
    ON DELETE SET NULL
);

-- Employees (sem alterações)
CREATE TABLE employees (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  last_name VARCHAR(255) NOT NULL,
  address TEXT,
  sex ENUM('male','female','other') NOT NULL,
  birth_date DATE NOT NULL,
  nationality VARCHAR(100),
  permission_type VARCHAR(100),
  email VARCHAR(255),
  ahv_number VARCHAR(50),
  phone VARCHAR(20),
  religion VARCHAR(50),
  marital_status ENUM('single','married','divorced','widowed'),
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
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
ALTER TABLE employees ADD INDEX idx_name (name);
ALTER TABLE employees ADD INDEX idx_role (role);

-- Inventory
CREATE TABLE inventory (
  id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('material','equipment','rented') NOT NULL,
  name VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tasks do projeto
CREATE TABLE tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  description TEXT NOT NULL,
  completed TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE
);

-- Resources alocados ao projeto (funcionários ou inventário)
CREATE TABLE project_resources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  project_id INT NOT NULL,
  resource_type ENUM('employee','inventory') NOT NULL,
  resource_id INT NOT NULL,
  quantity INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id)
    REFERENCES projects(id)
    ON DELETE CASCADE
);

-- Lembretes de calendário
CREATE TABLE reminders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  reminder_date DATE NOT NULL,
  color VARCHAR(20) DEFAULT '#00ff00',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Movimentações de estoque
CREATE TABLE inventory_movements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_name VARCHAR(255) NOT NULL,
  datetime DATETIME NOT NULL,
  reason ENUM('projeto','perda','adição','outros','criar') NOT NULL,
  project_id INT NULL,
  custom_reason TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (project_id)
    REFERENCES projects(id)
);

-- Detalhes das movimentações
CREATE TABLE inventory_movement_details (
  id INT AUTO_INCREMENT PRIMARY KEY,
  movement_id INT NOT NULL,
  item_id INT NOT NULL,
  quantity INT NOT NULL,
  FOREIGN KEY (movement_id)
    REFERENCES inventory_movements(id)
    ON DELETE CASCADE,
  FOREIGN KEY (item_id)
    REFERENCES inventory(id)
);

-- Pagamentos (Stripe, etc.)
CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intent_id VARCHAR(255) NOT NULL UNIQUE,
  amount INT NOT NULL,
  currency VARCHAR(10) NOT NULL,
  status VARCHAR(50) NOT NULL,
  created_at DATETIME NOT NULL
);

-- Faturas (invoices)
CREATE TABLE invoices (
  id INT AUTO_INCREMENT PRIMARY KEY,
  number VARCHAR(50),
  client_name VARCHAR(255),
  client_email VARCHAR(255),
  amount DECIMAL(10,2),
  issue_date DATE,
  due_date DATE,
  status VARCHAR(50)
);

-- Categorias de transação
CREATE TABLE finance_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  type ENUM('income','expense','debt') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transações financeiras
CREATE TABLE financial_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  type ENUM('income','expense','debt') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  date DATE NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)    REFERENCES users(id),
  FOREIGN KEY (category_id) REFERENCES finance_categories(id)
);

-- Comprovantes (anexos) de cada transação
CREATE TABLE transaction_attachments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transaction_id INT NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (transaction_id)
    REFERENCES financial_transactions(id)
    ON DELETE CASCADE
);

-- Dívidas específicas (facultativo: você pode tratar dívida como type='debt' em financial_transactions)
CREATE TABLE debts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT,
  transaction_id INT NULL,
  amount DECIMAL(12,2) NOT NULL,
  due_date DATE NOT NULL,
  status ENUM('open','paid','overdue') DEFAULT 'open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) 
    REFERENCES client(id),
  FOREIGN KEY (transaction_id)
    REFERENCES financial_transactions(id)
);
