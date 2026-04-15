<?php

include_once __DIR__ . '/db_helper.php';
include_once __DIR__ . '/audit_log.php';

if (!function_exists('request_rate_limit_allow')) {
    function request_rate_limit_allow($key, $maxAttempts, $windowSeconds, &$waitSeconds = 0)
    {
        $waitSeconds = 0;
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return true;
        }

        $bucketKey = '_rate_limit_' . (string)$key;
        $now = time();
        $state = $_SESSION[$bucketKey] ?? ['count' => 0, 'first' => $now];

        $count = (int)($state['count'] ?? 0);
        $first = (int)($state['first'] ?? $now);

        if (($now - $first) >= (int)$windowSeconds) {
            $count = 0;
            $first = $now;
        }

        if ($count >= (int)$maxAttempts) {
            $waitSeconds = max(1, (int)$windowSeconds - ($now - $first));
            $_SESSION[$bucketKey] = ['count' => $count, 'first' => $first];
            return false;
        }

        $count++;
        $_SESSION[$bucketKey] = ['count' => $count, 'first' => $first];
        return true;
    }
}

if (!function_exists('request_client_ip')) {
    function request_client_ip()
    {
        $remote = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
        return $remote !== '' ? $remote : '0.0.0.0';
    }
}

if (!function_exists('request_rate_limit_ensure_table')) {
    function request_rate_limit_ensure_table($conn)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS request_rate_limits (
                id INT AUTO_INCREMENT PRIMARY KEY,
                scope_key VARCHAR(80) NOT NULL,
                client_key VARCHAR(80) NOT NULL,
                hit_count INT NOT NULL DEFAULT 0,
                window_started_at INT NOT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_scope_client (scope_key, client_key),
                KEY idx_updated_at (updated_at)
            )
        ";

        if (mysqli_query($conn, $sql)) {
            $ready = true;
            return true;
        }

        return false;
    }
}

if (!function_exists('request_rate_limit_allow_ip')) {
    function request_rate_limit_allow_ip($conn, $scope, $maxAttempts, $windowSeconds, &$waitSeconds = 0)
    {
        $waitSeconds = 0;
        if (!$conn || !request_rate_limit_ensure_table($conn)) {
            return true;
        }

        $ip = request_client_ip();
        $clientKey = hash('sha256', $ip);
        $scopeKey = (string)$scope;
        $now = time();

        $stmt = db_stmt_execute(
            $conn,
            "SELECT hit_count, window_started_at
             FROM request_rate_limits
             WHERE scope_key = ? AND client_key = ?
             LIMIT 1",
            "ss",
            [$scopeKey, $clientKey]
        );
        $row = $stmt ? mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) : null;
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }

        if (!$row) {
            db_exec(
                $conn,
                "INSERT INTO request_rate_limits (scope_key, client_key, hit_count, window_started_at)
                 VALUES (?, ?, 1, ?)",
                "ssi",
                [$scopeKey, $clientKey, $now]
            );
            return true;
        }

        $count = (int)($row['hit_count'] ?? 0);
        $first = (int)($row['window_started_at'] ?? $now);
        if (($now - $first) >= (int)$windowSeconds) {
            db_exec(
                $conn,
                "UPDATE request_rate_limits
                 SET hit_count = 1, window_started_at = ?
                 WHERE scope_key = ? AND client_key = ?",
                "iss",
                [$now, $scopeKey, $clientKey]
            );
            return true;
        }

        if ($count >= (int)$maxAttempts) {
            $waitSeconds = max(1, (int)$windowSeconds - ($now - $first));
            return false;
        }

        db_exec(
            $conn,
            "UPDATE request_rate_limits
             SET hit_count = hit_count + 1
             WHERE scope_key = ? AND client_key = ?",
            "ss",
            [$scopeKey, $clientKey]
        );

        // Cleanup ringan untuk data lama.
        if (random_int(1, 100) <= 5) {
            $ttl = $now - max((int)$windowSeconds * 5, 900);
            db_exec(
                $conn,
                "DELETE FROM request_rate_limits WHERE window_started_at < ?",
                "i",
                [$ttl]
            );
        }

        return true;
    }
}

if (!function_exists('request_guard_log_rejected')) {
    function request_guard_log_rejected($conn, $context, $reason, array $details = [])
    {
        if (!$conn) {
            return false;
        }

        $payload = [
            'context' => (string)$context,
            'reason' => (string)$reason,
            'ip_hash' => hash('sha256', request_client_ip()),
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'details' => $details,
        ];

        return audit_log_write($conn, [
            'user_id' => null,
            'user_name' => 'request_guard',
            'user_role' => 'system',
            'action' => 'rejected_request',
            'entity' => 'security',
            'entity_id' => null,
            'details' => $payload,
        ]);
    }
}

if (!function_exists('request_normalize_text')) {
    function request_normalize_text($value, $maxLength = 255)
    {
        $value = trim((string)$value);
        $value = preg_replace('/\s+/', ' ', $value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, (int)$maxLength);
        }
        return substr($value, 0, (int)$maxLength);
    }
}

if (!function_exists('request_mb_len')) {
    function request_mb_len($value)
    {
        $value = (string)$value;
        return function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    }
}
