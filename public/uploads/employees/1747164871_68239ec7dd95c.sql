-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/03/2025 às 14:52
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `mvp_db`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `about` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `type` enum('material','equipment','rented') NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `inventory`
--

INSERT INTO `inventory` (`id`, `type`, `name`, `quantity`, `created_at`) VALUES
(1, 'material', 'Cimento CP II', 150, '2025-03-28 18:30:37'),
(2, 'material', 'Tijolo Cerâmico', 8500, '2025-03-28 18:30:37'),
(3, 'equipment', 'Betoneira', 3, '2025-03-28 18:30:37'),
(4, 'equipment', 'Furadeira Industrial', 5, '2025-03-28 18:30:37'),
(5, 'rented', 'Andaime Metálico', 12, '2025-03-28 18:30:37'),
(6, 'rented', 'Gerador de Energia', 2, '2025-03-28 18:30:37'),
(7, 'material', 'Tubo PVC 100mm', 45, '2025-03-28 18:30:37'),
(8, 'equipment', 'Compactador de Solo', 2, '2025-03-28 18:30:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `total_hours` int(11) DEFAULT 0,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `progress` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `projects`
--

INSERT INTO `projects` (`id`, `name`, `client_name`, `description`, `start_date`, `end_date`, `total_hours`, `status`, `progress`, `created_at`) VALUES
(1, 'Construção Residencial', '', 'Construção de casa padrão médio', '2024-03-01', '2024-09-30', 1200, 'in_progress', 35, '2025-03-28 18:30:36'),
(3, 'Ponte Metálica', '', 'Construção de ponte em estrutura metálica', '2024-01-10', '2024-12-15', 2500, 'in_progress', 60, '2025-03-28 18:30:36'),
(4, 'Manutenção Predial', '', 'Manutenção anual do edifício corporativo', '2024-05-01', '2024-05-31', 160, 'completed', 100, '2025-03-28 18:30:36');

-- --------------------------------------------------------

--
-- Estrutura para tabela `project_resources`
--

CREATE TABLE `project_resources` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `resource_type` enum('employee','inventory') NOT NULL,
  `resource_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `project_resources`
--

INSERT INTO `project_resources` (`id`, `project_id`, `resource_type`, `resource_id`, `quantity`, `created_at`) VALUES
(1, 1, 'employee', 1, 1, '2025-03-28 18:30:37'),
(2, 1, 'employee', 4, 1, '2025-03-28 18:30:37'),
(3, 1, 'inventory', 3, 2, '2025-03-28 18:30:37'),
(4, 1, 'inventory', 1, 50, '2025-03-28 18:30:37'),
(5, 3, 'employee', 2, 1, '2025-03-28 18:30:37'),
(6, 3, 'inventory', 5, 8, '2025-03-28 18:30:37'),
(7, 3, 'inventory', 8, 1, '2025-03-28 18:30:37'),
(8, 4, 'employee', 4, 1, '2025-03-28 18:30:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `project_resources`
--
ALTER TABLE `project_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `project_id` (`project_id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `project_resources`
--
ALTER TABLE `project_resources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `project_resources`
--
ALTER TABLE `project_resources`
  ADD CONSTRAINT `project_resources_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
