-- ================================================
-- Hospital Geral do Bengo
-- Migração v3: Novos tipos de atendimento
-- Alinha tipos_atendimento com especialidades
-- Executar DEPOIS de migracao_v2_especialidades.sql
-- ================================================

USE hospital_bengo;

-- Tipos actuais:
-- 1: Consulta Geral (N)
-- 2: Urgência / Emergência (U)
-- 3: Pediatria (N)
-- 4: Maternidade (G)

-- Novos tipos de atendimento
INSERT INTO tipos_atendimento 
    (nome, prefixo, activo) VALUES
('Cardiologia',   'N', 1),
('Ortopedia',     'N', 1),
('Oftalmologia',  'N', 1),
('Dermatologia',  'N', 1),
('Neurologia',    'N', 1),
('Ginecologia',   'N', 1),
('Otorrinolaringologia', 'N', 1),
('Cirurgia Geral','N', 1);

-- Novas especialidades que faltavam
INSERT IGNORE INTO especialidades 
    (nome, descricao, activo) VALUES
('Neurologia',    'Doenças do sistema nervoso',       1),
('Ginecologia',   'Saúde reprodutiva feminina',       1),
('Otorrinolaringologia', 'Ouvido, nariz e garganta',  1),
('Cirurgia Geral','Procedimentos cirúrgicos gerais',  1);
