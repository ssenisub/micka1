-- Script SQL pour créer la base de données Claude Clone
-- À exécuter dans phpMyAdmin ou via MySQL CLI

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS claude_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE claude_clone;

-- Table des utilisateurs (optionnelle, pour une future authentification)
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE,
    username VARCHAR(100),
    password_hash VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des conversations
CREATE TABLE IF NOT EXISTS conversations (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'Nouvelle conversation',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_updated_at (updated_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des messages
CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(36) PRIMARY KEY,
    conversation_id VARCHAR(36) NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    tokens INTEGER DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conversation_id (conversation_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour le stockage vectoriel (RAG - optionnel pour une version future)
CREATE TABLE IF NOT EXISTS documents (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    filename VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    embedding VECTOR(1024), -- Nécessite MySQL 8.0+ avec support vectoriel ou extension pgvector
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des logs d'audit (sécurité)
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36),
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id VARCHAR(36),
    ip_address VARCHAR(45),
    user_agent TEXT,
    details JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table des sessions (pour gestion de session personnalisée)
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id VARCHAR(36),
    data TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expires_at (expires_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion d'un utilisateur anonyme par défaut (optionnel)
INSERT INTO users (id, email, username) 
VALUES ('anonymous', 'anonymous@local', 'Anonymous')
ON DUPLICATE KEY UPDATE email=email;

-- Vue pour les statistiques d'utilisation
CREATE OR REPLACE VIEW conversation_stats AS
SELECT 
    c.id AS conversation_id,
    c.user_id,
    c.title,
    c.created_at,
    c.updated_at,
    COUNT(m.id) AS message_count,
    SUM(CASE WHEN m.role = 'user' THEN 1 ELSE 0 END) AS user_message_count,
    SUM(CASE WHEN m.role = 'assistant' THEN 1 ELSE 0 END) AS assistant_message_count,
    COALESCE(SUM(m.tokens), 0) AS total_tokens
FROM conversations c
LEFT JOIN messages m ON c.id = m.conversation_id
GROUP BY c.id, c.user_id, c.title, c.created_at, c.updated_at;

-- Procédure stockée pour nettoyer les anciennes sessions
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS cleanup_old_sessions()
BEGIN
    DELETE FROM sessions WHERE expires_at < NOW();
END //
DELIMITER ;

-- Trigger pour mettre à jour automatiquement le titre de la conversation après le premier message
DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_conversation_title_after_first_message
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    DECLARE msg_count INT;
    
    SELECT COUNT(*) INTO msg_count 
    FROM messages 
    WHERE conversation_id = NEW.conversation_id;
    
    IF msg_count = 1 AND NEW.role = 'user' THEN
        UPDATE conversations 
        SET title = LEFT(NEW.content, 50)
        WHERE id = NEW.conversation_id AND title = 'Nouvelle conversation';
    END IF;
END //
DELIMITER ;

-- Index full-text pour la recherche dans les messages (optionnel)
ALTER TABLE messages ADD FULLTEXT INDEX ft_content (content);

-- Commentaire sur les tables
ALTER TABLE conversations COMMENT 'Stocke les conversations utilisateur';
ALTER TABLE messages COMMENT 'Stocke les messages de chaque conversation';
ALTER TABLE documents COMMENT 'Stocke les documents pour le RAG (Recherche Augmentée par Génération)';
ALTER TABLE audit_logs COMMENT 'Logs d''audit pour la sécurité et la conformité';
ALTER TABLE sessions COMMENT 'Gestion des sessions utilisateur';
