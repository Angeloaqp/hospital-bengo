-- ================================================
-- Migração: 001 — Anti-Brute-Force + Prontuarios
-- Aplicar em instalações anteriores a 2026-05-13
-- ================================================
-- Para instalações NOVAS, usar schema.sql directamente.
-- Esta migração é apenas para bases existentes que
-- não têm estas colunas/tabelas.
-- ================================================

-- 1. Adicionar colunas de bloqueio à tabela utilizadores
ALTER TABLE `utilizadores`
  ADD COLUMN IF NOT EXISTS `tentativas_falhadas` tinyint(3) unsigned NOT NULL DEFAULT 0
    COMMENT 'Contador de tentativas de login falhadas'
    AFTER `estado`,
  ADD COLUMN IF NOT EXISTS `bloqueado_ate` datetime DEFAULT NULL
    COMMENT 'Timestamp ate quando a conta esta bloqueada'
    AFTER `tentativas_falhadas`;

-- 2. Adicionar coluna foto_path se não existir
ALTER TABLE `utilizadores`
  ADD COLUMN IF NOT EXISTS `foto_path` varchar(255) DEFAULT NULL
    AFTER `telefone`;

-- 3. Criar tabela prontuarios se não existir
CREATE TABLE IF NOT EXISTS `prontuarios` (
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

-- 4. Remover FK duplicada em tipos_atendimento (se existir)
-- MariaDB permite DROP FOREIGN KEY IF EXISTS
ALTER TABLE `tipos_atendimento`
  DROP FOREIGN KEY IF EXISTS `tipos_atendimento_ibfk_2`;
