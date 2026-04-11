-- ================================================
-- Hospital Geral do Bengo
-- Migração v5: Sistema de Mensagens Internas
-- ================================================

USE hospital_bengo;

CREATE TABLE IF NOT EXISTS mensagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    remetente_id INT UNSIGNED NOT NULL,
    destinatario_id INT UNSIGNED NOT NULL,
    assunto VARCHAR(255) NOT NULL,
    conteudo TEXT NOT NULL,
    lida TINYINT(1) DEFAULT 0 COMMENT '0=nao lida, 1=lida',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (remetente_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    FOREIGN KEY (destinatario_id) REFERENCES utilizadores(id) ON DELETE CASCADE,
    INDEX idx_remetente (remetente_id),
    INDEX idx_destinatario (destinatario_id),
    INDEX idx_lida (lida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
