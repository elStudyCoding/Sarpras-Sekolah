<?php

include_once __DIR__ . '/db_helper.php';

if (!function_exists('audit_log_ensure_table')) {
    function audit_log_ensure_table($conn)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NULL,
                user_name VARCHAR(120) NOT NULL,
                user_role VARCHAR(30) NOT NULL,
                action VARCHAR(100) NOT NULL,
                entity VARCHAR(60) NOT NULL,
                entity_id INT NULL,
                details TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ";

        if (mysqli_query($conn, $sql)) {
            $ready = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('audit_log_write')) {
    function audit_log_write($conn, array $payload)
    {
        if (!audit_log_ensure_table($conn)) {
            return false;
        }

        $userId = isset($payload['user_id']) ? (int)$payload['user_id'] : null;
        $userName = (string)($payload['user_name'] ?? 'system');
        $userRole = (string)($payload['user_role'] ?? 'unknown');
        $action = (string)($payload['action'] ?? 'unknown_action');
        $entity = (string)($payload['entity'] ?? 'unknown_entity');
        $entityId = isset($payload['entity_id']) ? (int)$payload['entity_id'] : null;
        $details = isset($payload['details']) ? json_encode($payload['details'], JSON_UNESCAPED_UNICODE) : null;

        return db_exec(
            $conn,
            "INSERT INTO audit_logs (user_id, user_name, user_role, action, entity, entity_id, details) VALUES (?, ?, ?, ?, ?, ?, ?)",
            "issssis",
            [$userId, $userName, $userRole, $action, $entity, $entityId, $details]
        );
    }
}
