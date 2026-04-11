-- ================================================
-- Hospital Geral do Bengo
-- Migração v4: Sistema de Auditoria
-- ================================================

USE hospital_bengo;

CREATE TABLE IF NOT EXISTS logs_auditoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilizador_id INT UNSIGNED NOT NULL,
    accao VARCHAR(100) NOT NULL
        COMMENT 'Ex: login, chamar_paciente, criar_utilizador',
    detalhes TEXT DEFAULT NULL
        COMMENT 'Informação adicional em texto livre',
    ip VARCHAR(45) DEFAULT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilizador_id)
        REFERENCES utilizadores(id) ON UPDATE CASCADE,
    INDEX idx_accao (accao),
    INDEX idx_criado_em (criado_em),
    INDEX idx_utilizador (utilizador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
