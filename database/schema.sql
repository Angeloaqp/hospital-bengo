-- ================================================
-- Hospital Geral do Bengo — Schema Completo
-- Compatível com MariaDB 10.4+ / MySQL 8.x
-- Última actualização: 2026-05-13
-- ================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------
-- 1. Consultórios
-- ------------------------------------------------
DROP TABLE IF EXISTS `consultorios`;
CREATE TABLE `consultorios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `responsavel` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 2. Especialidades
-- ------------------------------------------------
DROP TABLE IF EXISTS `especialidades`;
CREATE TABLE `especialidades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 3. Utilizadores
-- ------------------------------------------------
DROP TABLE IF EXISTS `utilizadores`;
CREATE TABLE `utilizadores` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `nome_utilizador` varchar(50) NOT NULL,
  `senha_hash` varchar(255) NOT NULL,
  `perfil` enum('recepcionista','medico','admin') NOT NULL,
  `especialidade_id` int(10) unsigned DEFAULT NULL,
  `consultorio_id` int(10) unsigned DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `foto_path` varchar(255) DEFAULT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=activo, 0=inactivo',
  `tentativas_falhadas` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Contador de tentativas de login falhadas',
  `bloqueado_ate` datetime DEFAULT NULL COMMENT 'Timestamp ate quando a conta esta bloqueada',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome_utilizador` (`nome_utilizador`),
  KEY `idx_perfil` (`perfil`),
  KEY `idx_estado` (`estado`),
  KEY `especialidade_id` (`especialidade_id`),
  KEY `consultorio_id` (`consultorio_id`),
  CONSTRAINT `utilizadores_ibfk_1` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `utilizadores_ibfk_2` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 4. Tipos de Atendimento
-- ------------------------------------------------
DROP TABLE IF EXISTS `tipos_atendimento`;
CREATE TABLE `tipos_atendimento` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `prefixo` varchar(2) NOT NULL COMMENT 'Ex: N, U, I, G',
  `especialidade_id` int(10) unsigned DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `especialidade_id` (`especialidade_id`),
  CONSTRAINT `tipos_atendimento_ibfk_1` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 5. Pacientes
-- ------------------------------------------------
DROP TABLE IF EXISTS `pacientes`;
CREATE TABLE `pacientes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(150) NOT NULL,
  `bi_nif` varchar(20) DEFAULT NULL,
  `idade` tinyint(3) unsigned NOT NULL,
  `morada` varchar(200) NOT NULL,
  `peso` decimal(5,2) DEFAULT NULL COMMENT 'Apenas para menores de 18 anos',
  `registado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `registado_por` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bi_nif` (`bi_nif`),
  KEY `registado_por` (`registado_por`),
  KEY `idx_registado_em` (`registado_em`),
  CONSTRAINT `pacientes_ibfk_1` FOREIGN KEY (`registado_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 6. Senhas (Fila de Espera)
-- ------------------------------------------------
DROP TABLE IF EXISTS `senhas`;
CREATE TABLE `senhas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(10) NOT NULL COMMENT 'Ex: U-001, N-012, I-003, G-002',
  `paciente_id` int(10) unsigned NOT NULL,
  `tipo_atendimento_id` int(10) unsigned NOT NULL,
  `consultorio_id` int(10) unsigned DEFAULT NULL,
  `prioridade` tinyint(3) unsigned NOT NULL COMMENT '1=Urgente, 2=Idoso, 3=Gravida, 4=Normal',
  `estado` enum('espera','chamada','concluida','cancelada') NOT NULL DEFAULT 'espera',
  `registado_por` int(10) unsigned NOT NULL,
  `atendido_por` int(10) unsigned DEFAULT NULL,
  `hora_chamada` timestamp NULL DEFAULT NULL,
  `hora_conclusao` timestamp NULL DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `tipo_atendimento_id` (`tipo_atendimento_id`),
  KEY `consultorio_id` (`consultorio_id`),
  KEY `registado_por` (`registado_por`),
  KEY `atendido_por` (`atendido_por`),
  KEY `idx_estado` (`estado`),
  KEY `idx_prioridade` (`prioridade`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `senhas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_2` FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_3` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_4` FOREIGN KEY (`registado_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_5` FOREIGN KEY (`atendido_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 7. Prontuarios (Registos Clinicos)
-- ------------------------------------------------
DROP TABLE IF EXISTS `prontuarios`;
CREATE TABLE `prontuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `senha_id` int(10) unsigned NOT NULL,
  `paciente_id` int(10) unsigned NOT NULL,
  `medico_id` int(10) unsigned NOT NULL,
  `notas_clinicas` text DEFAULT NULL,
  `prescricao` text DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `senha_id` (`senha_id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `medico_id` (`medico_id`),
  CONSTRAINT `prontuarios_ibfk_1` FOREIGN KEY (`senha_id`) REFERENCES `senhas` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prontuarios_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `prontuarios_ibfk_3` FOREIGN KEY (`medico_id`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 8. Logs de Auditoria
-- ------------------------------------------------
DROP TABLE IF EXISTS `logs_auditoria`;
CREATE TABLE `logs_auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `utilizador_id` int(10) unsigned NOT NULL,
  `accao` varchar(100) NOT NULL COMMENT 'Ex: login, chamar_paciente, criar_utilizador',
  `detalhes` text DEFAULT NULL COMMENT 'Informacao adicional em texto livre',
  `ip` varchar(45) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_accao` (`accao`),
  KEY `idx_criado_em` (`criado_em`),
  KEY `idx_utilizador` (`utilizador_id`),
  CONSTRAINT `logs_auditoria_ibfk_1` FOREIGN KEY (`utilizador_id`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 9. Mensagens Internas
-- ------------------------------------------------
DROP TABLE IF EXISTS `mensagens`;
CREATE TABLE `mensagens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `remetente_id` int(10) unsigned NOT NULL,
  `destinatario_id` int(10) unsigned NOT NULL,
  `assunto` varchar(255) NOT NULL,
  `conteudo` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0 COMMENT '0=nao lida, 1=lida',
  `apagada_remetente` tinyint(1) DEFAULT 0,
  `apagada_destinatario` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_remetente` (`remetente_id`),
  KEY `idx_destinatario` (`destinatario_id`),
  KEY `idx_lida` (`lida`),
  CONSTRAINT `mensagens_ibfk_1` FOREIGN KEY (`remetente_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mensagens_ibfk_2` FOREIGN KEY (`destinatario_id`) REFERENCES `utilizadores` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================
-- DADOS INICIAIS MINIMOS
-- ================================================

-- Consultorios
INSERT INTO `consultorios` (`nome`, `responsavel`, `activo`) VALUES
('Consultorio 1', NULL, 1),
('Consultorio 2', NULL, 1),
('Consultorio 3', NULL, 1),
('Consultorio 4', NULL, 1);

-- Especialidades
INSERT INTO `especialidades` (`nome`, `descricao`, `activo`) VALUES
('Geral', 'Medicina geral e familiar', 1),
('Pediatria', 'Atendimento infantil', 1),
('Maternidade', 'Cuidados materno-infantis', 1),
('Cardiologia', 'Sistema cardiovascular', 1),
('Ortopedia', 'Sistema musculo-esqueletico', 1),
('Oftalmologia', 'Cuidados visuais', 1),
('Estomatologia', 'Saude oral', 1),
('Urgencia / Emergencia', 'Atendimento urgente e emergencias', 1);

-- Tipos de Atendimento
INSERT INTO `tipos_atendimento` (`nome`, `prefixo`, `especialidade_id`, `activo`) VALUES
('Geral', 'N', 1, 1),
('Pediatria', 'N', 2, 1),
('Maternidade', 'G', 3, 1),
('Cardiologia', 'N', 4, 1),
('Ortopedia', 'N', 5, 1),
('Oftalmologia', 'N', 6, 1),
('Estomatologia', 'N', 7, 1),
('Urgência / Emergência', 'U', 8, 1);

-- Utilizadores padrao (senha: Hospital@2025)
INSERT INTO `utilizadores` (`nome`, `nome_utilizador`, `senha_hash`, `perfil`, `especialidade_id`, `consultorio_id`, `estado`) VALUES
('Administrador', 'admin', '$2y$10$YfPFz1.t6mBwVNH1bGEJa.kT8a8cSxU1qQx9aH.dGOaEPwF5Gm1mK', 'admin', NULL, NULL, 1),
('Dr. Exemplo', 'medico', '$2y$10$YfPFz1.t6mBwVNH1bGEJa.kT8a8cSxU1qQx9aH.dGOaEPwF5Gm1mK', 'medico', 1, 1, 1),
('Recepcao Geral', 'recepcao', '$2y$10$YfPFz1.t6mBwVNH1bGEJa.kT8a8cSxU1qQx9aH.dGOaEPwF5Gm1mK', 'recepcionista', NULL, NULL, 1);
