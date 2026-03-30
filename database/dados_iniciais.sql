-- ================================================
-- Hospital Geral do Bengo
-- Dados Iniciais — Configuração e Utilizadores de Teste
-- IMPORTANTE: Executar DEPOIS do schema.sql
-- ================================================

USE hospital_bengo;

-- ------------------------------------------------
-- Tipos de Atendimento (4 serviços)
-- ------------------------------------------------
INSERT INTO tipos_atendimento 
    (nome, prefixo, activo) VALUES
('Consulta Geral',       'N', 1),
('Urgência / Emergência','U', 1),
('Pediatria',            'N', 1),
('Maternidade',          'G', 1);

-- ------------------------------------------------
-- Consultórios (3 consultórios)
-- ------------------------------------------------
INSERT INTO consultorios 
    (nome, responsavel, activo) VALUES
('Consultório 1', 'Dr. António Silva',  1),
('Consultório 2', 'Dr. Carlos Mendes',  1),
('Consultório 3', 'Dra. Maria Lopes',   1);

-- ------------------------------------------------
-- Utilizadores de Teste (senha: Hospital@2025)
-- Hash gerado com password_hash() do PHP 8
-- ------------------------------------------------
INSERT INTO utilizadores 
    (nome, nome_utilizador, senha_hash, perfil, estado) 
VALUES
(
    'Administrador do Sistema',
    'admin',
    '$2y$10$heqyT/gVfG4vtb4PgjT6IOTJPTcNKEHMlY4pKdLR17rM4rN3E7F2e',
    'admin',
    1
),
(
    'Ana Paula Rodrigues',
    'recepcao',
    '$2y$10$heqyT/gVfG4vtb4PgjT6IOTJPTcNKEHMlY4pKdLR17rM4rN3E7F2e',
    'recepcionista',
    1
),
(
    'Dr. Carlos Mendes',
    'medico',
    '$2y$10$heqyT/gVfG4vtb4PgjT6IOTJPTcNKEHMlY4pKdLR17rM4rN3E7F2e',
    'medico',
    1
);

-- ------------------------------------------------
-- CREDENCIAIS DE TESTE:
-- Utilizador: admin     | Senha: Hospital@2025
-- Utilizador: recepcao  | Senha: Hospital@2025  
-- Utilizador: medico    | Senha: Hospital@2025
-- ------------------------------------------------
