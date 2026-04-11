-- ================================================
-- Hospital Geral do Bengo
-- Migração v6: Foto de Perfil
-- ================================================

USE hospital_bengo;

ALTER TABLE utilizadores
ADD COLUMN foto_path VARCHAR(255) DEFAULT NULL;
