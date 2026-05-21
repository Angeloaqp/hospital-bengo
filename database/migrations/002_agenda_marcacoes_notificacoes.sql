-- ================================================
-- Hospital Geral do Bengo — Migração 002
-- Agenda Central: Marcações, Triagem, Notificações
-- Data: 2026-05-18
-- ================================================
-- IMPORTANTE: Executar após 001_prontuarios_e_bloqueio.sql
-- Esta migração é idempotente (usa IF NOT EXISTS).
-- ================================================

SET NAMES utf8mb4;

-- ------------------------------------------------
-- 1. Tabela: marcacoes (Agenda Central)
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `marcacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int(10) unsigned NOT NULL,
  `especialidade_id` int(10) unsigned NOT NULL,
  `tipo_atendimento_id` int(10) unsigned NOT NULL,
  `consultorio_id` int(10) unsigned DEFAULT NULL,
  `medico_id` int(10) unsigned NOT NULL,
  `data_consulta` date NOT NULL,
  `turno` enum('manha','tarde') NOT NULL,
  `origem` enum('marcacao','mesmo_dia') NOT NULL DEFAULT 'marcacao',
  `prioridade` tinyint(3) unsigned NOT NULL DEFAULT 4 COMMENT '1=Urgente, 2=Idoso, 3=Gravida, 4=Normal',
  `estado` enum('marcada','confirmada','em_atendimento','concluida','cancelada','falta','remarcada') NOT NULL DEFAULT 'marcada',
  `observacoes` text DEFAULT NULL,
  `criada_por` int(10) unsigned NOT NULL,
  `remarcada_de_id` int(10) unsigned DEFAULT NULL COMMENT 'ID da marcacao original quando remarcada',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_marcacoes_data` (`data_consulta`),
  KEY `idx_marcacoes_medico` (`medico_id`),
  KEY `idx_marcacoes_estado` (`estado`),
  KEY `idx_marcacoes_paciente` (`paciente_id`),
  KEY `idx_marcacoes_medico_data_turno` (`medico_id`, `data_consulta`, `turno`),
  KEY `idx_marcacoes_remarcada` (`remarcada_de_id`),
  CONSTRAINT `fk_marcacoes_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_especialidade` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_tipo` FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_consultorio` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_medico` FOREIGN KEY (`medico_id`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_criada_por` FOREIGN KEY (`criada_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_marcacoes_remarcada` FOREIGN KEY (`remarcada_de_id`) REFERENCES `marcacoes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 2. Tabela: triagens
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `triagens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `marcacao_id` int(10) unsigned NOT NULL,
  `paciente_id` int(10) unsigned NOT NULL,
  `sintomas` text DEFAULT NULL,
  `temperatura` decimal(4,1) DEFAULT NULL COMMENT 'Graus Celsius',
  `pressao_arterial` varchar(20) DEFAULT NULL COMMENT 'Ex: 120/80',
  `peso` decimal(5,2) DEFAULT NULL COMMENT 'Kg',
  `frequencia_cardiaca` int(10) unsigned DEFAULT NULL COMMENT 'bpm',
  `observacoes` text DEFAULT NULL,
  `prioridade_clinica` tinyint(3) unsigned NOT NULL DEFAULT 4 COMMENT '1=Urgente, 2=Alta, 3=Moderada, 4=Normal',
  `registado_por` int(10) unsigned NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_triagens_marcacao` (`marcacao_id`),
  KEY `idx_triagens_paciente` (`paciente_id`),
  CONSTRAINT `fk_triagens_marcacao` FOREIGN KEY (`marcacao_id`) REFERENCES `marcacoes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_triagens_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_triagens_registado_por` FOREIGN KEY (`registado_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 3. Tabela: paciente_contactos
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `paciente_contactos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `paciente_id` int(10) unsigned NOT NULL,
  `tipo` enum('telefone','whatsapp','email','emergencia') NOT NULL,
  `valor` varchar(150) NOT NULL,
  `nome_contacto` varchar(100) DEFAULT NULL COMMENT 'Nome do contacto (para emergencia)',
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `consentimento` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=paciente autorizou envio de lembretes',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_contactos_paciente_tipo` (`paciente_id`, `tipo`),
  KEY `idx_contactos_valor` (`valor`),
  CONSTRAINT `fk_contactos_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 4. Tabela: disponibilidades_medicas
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `disponibilidades_medicas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `medico_id` int(10) unsigned NOT NULL,
  `especialidade_id` int(10) unsigned NOT NULL,
  `consultorio_id` int(10) unsigned DEFAULT NULL,
  `dia_semana` tinyint(3) unsigned NOT NULL COMMENT '1=Segunda ... 7=Domingo',
  `turno` enum('manha','tarde') NOT NULL,
  `capacidade` int(10) unsigned NOT NULL DEFAULT 10 COMMENT 'Max pacientes por turno',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_disponibilidade_medico_dia_turno` (`medico_id`, `dia_semana`, `turno`),
  KEY `idx_disponibilidades_medico` (`medico_id`),
  CONSTRAINT `fk_disponibilidades_medico` FOREIGN KEY (`medico_id`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_disponibilidades_especialidade` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_disponibilidades_consultorio` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 5. Tabela: bloqueios_agenda
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `bloqueios_agenda` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `medico_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = bloqueio geral',
  `consultorio_id` int(10) unsigned DEFAULT NULL COMMENT 'NULL = todos os consultorios',
  `data_bloqueio` date NOT NULL,
  `turno` enum('manha','tarde') NOT NULL,
  `motivo` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_por` int(10) unsigned NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bloqueios_medico_data` (`medico_id`, `data_bloqueio`),
  KEY `idx_bloqueios_data` (`data_bloqueio`),
  CONSTRAINT `fk_bloqueios_medico` FOREIGN KEY (`medico_id`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bloqueios_consultorio` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_bloqueios_criado_por` FOREIGN KEY (`criado_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 6. Tabela: notificacoes
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS `notificacoes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `marcacao_id` int(10) unsigned NOT NULL,
  `paciente_id` int(10) unsigned NOT NULL,
  `canal` enum('sms','whatsapp','email') NOT NULL,
  `destino` varchar(150) NOT NULL COMMENT 'Telefone ou email de destino',
  `assunto` varchar(255) DEFAULT NULL COMMENT 'Assunto (apenas para email)',
  `conteudo` text NOT NULL,
  `agendada_para` datetime NOT NULL,
  `enviada_em` datetime DEFAULT NULL,
  `estado` enum('pendente','enviada','falhada','cancelada') NOT NULL DEFAULT 'pendente',
  `tentativas` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `ultimo_erro` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notificacoes_estado_agendada` (`estado`, `agendada_para`),
  KEY `idx_notificacoes_marcacao` (`marcacao_id`),
  KEY `idx_notificacoes_paciente` (`paciente_id`),
  CONSTRAINT `fk_notificacoes_marcacao` FOREIGN KEY (`marcacao_id`) REFERENCES `marcacoes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_notificacoes_paciente` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 7. Alterar tabela: senhas
-- Adicionar colunas para vincular à marcação
-- ------------------------------------------------

-- Coluna marcacao_id (nullable para compatibilidade)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'senhas' AND COLUMN_NAME = 'marcacao_id');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `senhas` ADD COLUMN `marcacao_id` int(10) unsigned DEFAULT NULL AFTER `id`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Coluna origem
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'senhas' AND COLUMN_NAME = 'origem');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `senhas` ADD COLUMN `origem` enum(''fila'',''marcacao'',''mesmo_dia'') NOT NULL DEFAULT ''fila'' AFTER `marcacao_id`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK marcacao_id → marcacoes(id)
SET @fk_exists = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'senhas' AND CONSTRAINT_NAME = 'fk_senhas_marcacao');
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `senhas` ADD KEY `idx_senhas_marcacao` (`marcacao_id`), ADD CONSTRAINT `fk_senhas_marcacao` FOREIGN KEY (`marcacao_id`) REFERENCES `marcacoes` (`id`) ON UPDATE CASCADE', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ------------------------------------------------
-- 8. Alterar tabela: pacientes
-- Adicionar campos de contacto e dados demográficos
-- ------------------------------------------------

-- telefone
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'telefone');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `telefone` varchar(20) DEFAULT NULL AFTER `morada`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- email
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'email');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `email` varchar(150) DEFAULT NULL AFTER `telefone`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- sexo
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'sexo');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `sexo` enum(''M'',''F'') DEFAULT NULL AFTER `email`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- data_nascimento
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'data_nascimento');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `data_nascimento` date DEFAULT NULL AFTER `sexo`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- contacto_emergencia_nome
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'contacto_emergencia_nome');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `contacto_emergencia_nome` varchar(100) DEFAULT NULL AFTER `data_nascimento`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- contacto_emergencia_telefone
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'pacientes' AND COLUMN_NAME = 'contacto_emergencia_telefone');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `pacientes` ADD COLUMN `contacto_emergencia_telefone` varchar(20) DEFAULT NULL AFTER `contacto_emergencia_nome`', 
    'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ================================================
-- FIM DA MIGRAÇÃO 002
-- ================================================
