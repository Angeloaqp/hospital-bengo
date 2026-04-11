-- ================================================
-- Hospital Geral do Bengo
-- Migração v2: Especialidades + Utilizadores expandido
-- Executar DEPOIS do schema.sql e dados_iniciais.sql
-- ================================================

USE hospital_bengo;

-- ------------------------------------------------
-- TABELA: especialidades
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS especialidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- Dados iniciais de especialidades
-- ------------------------------------------------
INSERT INTO especialidades (nome, descricao, activo) VALUES
('Clínica Geral',    'Atendimento geral e triagem',         1),
('Pediatria',        'Atendimento a crianças e adolescentes',1),
('Maternidade',      'Acompanhamento pré-natal e parto',    1),
('Urgência',         'Atendimento de emergência',           1),
('Cardiologia',      'Doenças do coração e sistema cardiovascular', 1),
('Ortopedia',        'Fraturas, ossos e articulações',      1),
('Oftalmologia',     'Doenças dos olhos e visão',           1),
('Dermatologia',     'Doenças da pele',                     1);

-- ------------------------------------------------
-- Expandir tabela utilizadores
-- ------------------------------------------------
ALTER TABLE utilizadores
    ADD COLUMN especialidade_id INT UNSIGNED DEFAULT NULL 
        AFTER perfil,
    ADD COLUMN consultorio_id INT UNSIGNED DEFAULT NULL 
        AFTER especialidade_id,
    ADD COLUMN telefone VARCHAR(20) DEFAULT NULL 
        AFTER consultorio_id,
    ADD FOREIGN KEY (especialidade_id) 
        REFERENCES especialidades(id) ON UPDATE CASCADE,
    ADD FOREIGN KEY (consultorio_id) 
        REFERENCES consultorios(id) ON UPDATE CASCADE;

-- ------------------------------------------------
-- Associar médico existente à Clínica Geral
-- ------------------------------------------------
UPDATE utilizadores 
SET especialidade_id = 1, consultorio_id = 1 
WHERE nome_utilizador = 'medico';
