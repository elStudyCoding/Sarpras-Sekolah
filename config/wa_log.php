<?php

include_once __DIR__ . '/db_helper.php';

if (!function_exists('wa_log_ensure_table')) {
    function wa_log_ensure_table($conn)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $sql = "
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
            )
        ";

        if (mysqli_query($conn, $sql)) {
            $ready = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('wa_log_write')) {
    function wa_log_write($conn, array $payload)
    {
        if (!wa_log_ensure_table($conn)) {
            return false;
        }

        $context = (string)($payload['context'] ?? 'unknown');
        $targetPhone = (string)($payload['target_phone'] ?? '');
        $message = (string)($payload['message'] ?? '');
        $status = (string)($payload['status'] ?? 'unknown');
        $provider = (string)($payload['provider'] ?? 'unknown');
        $providerError = (string)($payload['provider_error'] ?? '');
        $entityType = isset($payload['entity_type']) ? (string)$payload['entity_type'] : null;
        $entityId = isset($payload['entity_id']) ? (int)$payload['entity_id'] : null;

        return db_exec(
            $conn,
            "INSERT INTO wa_notification_logs (context, target_phone, message, status, provider, provider_error, entity_type, entity_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            "sssssssi",
            [$context, $targetPhone, $message, $status, $provider, $providerError, $entityType, $entityId]
        );
    }
}
