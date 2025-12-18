-- SQL script to bootstrap the lc_system database
CREATE DATABASE IF NOT EXISTS lc_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lc_system;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, role_name) VALUES
    (1, 'Администратор'),
    (2, 'Редактор'),
    (3, 'Наблюдатель')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    passkey_label VARCHAR(255) NOT NULL UNIQUE,
    passkey_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username, email, passkey_label, passkey_hash, role_id) VALUES
    ('Админ', 'admin@example.com', 'admin-passkey', '$2y$12$TM7gGCRfTERmRo6Qpn5jA.lkwsZ7qRlImvG0CO4ekHKOO2J8o8G1.', 1),
    ('Редактор', 'editor@example.com', 'editor-passkey', '$2y$12$cl07bWxiEAlhMUumCjbq3uT5pLzv/nPvyH1AKtdYzvlcF4yUFgxde', 2),
    ('Наблюдатель', 'viewer@example.com', 'viewer-passkey', '$2y$12$QoOCFXrtyKWSgs9tmN92kunqRerFeF0A1Klvpw.zXnFwvxrFeTvJ6', 3)
ON DUPLICATE KEY UPDATE
    username = VALUES(username),
    role_id = VALUES(role_id);

CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_path VARCHAR(255) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS document_acl (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_path VARCHAR(255) NOT NULL,
    user_id INT NULL,
    role_id INT NULL,
    can_read TINYINT(1) DEFAULT 1,
    can_write TINYINT(1) DEFAULT 0,
    UNIQUE KEY uniq_acl (document_path, user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO documents (document_path, title, created_by) VALUES
    ('docs/architecture.md', 'Архитектура системы', 1),
    ('docs/policy.md', 'Политика доступа', 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);

INSERT INTO document_acl (document_path, role_id, can_read, can_write) VALUES
    ('docs/architecture.md', 2, 1, 1),
    ('docs/architecture.md', 3, 1, 0),
    ('docs/policy.md', 2, 1, 1),
    ('docs/policy.md', 3, 1, 0)
ON DUPLICATE KEY UPDATE
    can_read = VALUES(can_read),
    can_write = VALUES(can_write);
