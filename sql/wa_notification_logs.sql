CREATE TABLE IF NOT EXISTS wa_notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context VARCHAR(40) NOT NULL,
    target_phone VARCHAR(25) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(15) NOT NULL,
    provider VARCHAR(20) NOT NULL,
    provider_error TEXT NULL,
    entity_type VARCHAR(40) NULL,
    entity_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_created_at (created_at),
    KEY idx_status (status),
    KEY idx_context (context)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
