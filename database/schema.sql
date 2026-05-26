-- ================================================
-- Hospital Geral do Bengo — Schema Completo
-- Compatível com MariaDB 10.4+ / MySQL 8.x
-- Última actualização: 2026-05-18
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
  `numero_processo` varchar(20) UNIQUE DEFAULT NULL,
  `nome` varchar(150) NOT NULL,
  `bi_nif` varchar(20) DEFAULT NULL,
  `idade` tinyint(3) unsigned NOT NULL,
  `morada` varchar(200) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `sexo` enum('M','F') DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `contacto_emergencia_nome` varchar(100) DEFAULT NULL,
  `contacto_emergencia_telefone` varchar(20) DEFAULT NULL,
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
-- 6. Marcações (Agenda Central)
-- ------------------------------------------------
DROP TABLE IF EXISTS `marcacoes`;
CREATE TABLE `marcacoes` (
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
-- 7. Senhas (Fila de Espera)
-- ------------------------------------------------
DROP TABLE IF EXISTS `senhas`;
CREATE TABLE `senhas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `marcacao_id` int(10) unsigned DEFAULT NULL COMMENT 'Null para senhas legacy sem marcacao',
  `origem` enum('fila','marcacao','mesmo_dia') NOT NULL DEFAULT 'fila',
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
  KEY `idx_senhas_marcacao` (`marcacao_id`),
  CONSTRAINT `senhas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_2` FOREIGN KEY (`tipo_atendimento_id`) REFERENCES `tipos_atendimento` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_3` FOREIGN KEY (`consultorio_id`) REFERENCES `consultorios` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_4` FOREIGN KEY (`registado_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `senhas_ibfk_5` FOREIGN KEY (`atendido_por`) REFERENCES `utilizadores` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_senhas_marcacao` FOREIGN KEY (`marcacao_id`) REFERENCES `marcacoes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- 8. Triagens
-- ------------------------------------------------
DROP TABLE IF EXISTS `triagens`;
CREATE TABLE `triagens` (
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
-- 9. Contactos de Paciente
-- ------------------------------------------------
DROP TABLE IF EXISTS `paciente_contactos`;
CREATE TABLE `paciente_contactos` (
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
-- 10. Disponibilidades Médicas
-- ------------------------------------------------
DROP TABLE IF EXISTS `disponibilidades_medicas`;
CREATE TABLE `disponibilidades_medicas` (
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
-- 11. Bloqueios de Agenda
-- ------------------------------------------------
DROP TABLE IF EXISTS `bloqueios_agenda`;
CREATE TABLE `bloqueios_agenda` (
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
-- 12. Notificações (Lembretes)
-- ------------------------------------------------
DROP TABLE IF EXISTS `notificacoes`;
CREATE TABLE `notificacoes` (
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
-- 13. Prontuarios (Registos Clinicos)
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
-- 14. Logs de Auditoria
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
-- 15. Mensagens Internas
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
