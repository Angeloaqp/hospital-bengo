-- ================================================
-- Hospital Geral do Bengo
-- Sistema de Gestão de Filas Hospitalares
-- Base de Dados — Estrutura das Tabelas
-- Versão: 1.0 | PHP 8.2 | MySQL 8.x
-- ================================================

CREATE DATABASE IF NOT EXISTS hospital_bengo
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE hospital_bengo;

-- ------------------------------------------------
-- TABELA 1: utilizadores
-- Armazena as contas de acesso ao sistema
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS utilizadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    nome_utilizador VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('recepcionista', 'medico', 'admin') NOT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1 
        COMMENT '1=activo, 0=inactivo',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_perfil (perfil),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- TABELA 2: tipos_atendimento
-- Tabela de configuração dos serviços do hospital
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS tipos_atendimento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    prefixo VARCHAR(2) NOT NULL COMMENT 'Ex: N, U, I, G',
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- TABELA 3: consultorios
-- Regista os consultórios disponíveis no hospital
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS consultorios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    responsavel VARCHAR(100) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- TABELA 4: pacientes
-- Armazena os dados de cada paciente registado
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    idade TINYINT UNSIGNED NOT NULL,
    morada VARCHAR(200) NOT NULL,
    peso DECIMAL(5,2) DEFAULT NULL 
        COMMENT 'Apenas para menores de 18 anos',
    registado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registado_por INT UNSIGNED NOT NULL,
    FOREIGN KEY (registado_por) 
        REFERENCES utilizadores(id) 
        ON UPDATE CASCADE,
    INDEX idx_registado_em (registado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------
-- TABELA 5: senhas
-- Entidade central — controla toda a fila
-- ------------------------------------------------
CREATE TABLE IF NOT EXISTS senhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL 
        COMMENT 'Ex: U-001, N-012, I-003, G-002',
    paciente_id INT UNSIGNED NOT NULL,
    tipo_atendimento_id INT UNSIGNED NOT NULL,
    consultorio_id INT UNSIGNED DEFAULT NULL,
    prioridade TINYINT UNSIGNED NOT NULL 
        COMMENT '1=Urgente,2=Idoso,3=Gravida,4=Normal',
    estado ENUM('espera','chamada','concluida','cancelada') 
        NOT NULL DEFAULT 'espera',
    registado_por INT UNSIGNED NOT NULL,
    atendido_por INT UNSIGNED DEFAULT NULL,
    hora_chamada TIMESTAMP NULL DEFAULT NULL,
    hora_conclusao TIMESTAMP NULL DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id) 
        REFERENCES pacientes(id) ON UPDATE CASCADE,
    FOREIGN KEY (tipo_atendimento_id) 
        REFERENCES tipos_atendimento(id) ON UPDATE CASCADE,
    FOREIGN KEY (consultorio_id) 
        REFERENCES consultorios(id) ON UPDATE CASCADE,
    FOREIGN KEY (registado_por) 
        REFERENCES utilizadores(id) ON UPDATE CASCADE,
    FOREIGN KEY (atendido_por) 
        REFERENCES utilizadores(id) ON UPDATE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_prioridade (prioridade),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
  COLLATE=utf8mb4_unicode_ci;
