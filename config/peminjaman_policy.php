<?php

include_once __DIR__ . '/db_helper.php';
include_once __DIR__ . '/peminjaman_schema.php';

if (!defined('PENALTY_POINTS_OVERDUE')) {
    define('PENALTY_POINTS_OVERDUE', 10);
}

if (!defined('PENALTY_SUSPEND_THRESHOLD')) {
    define('PENALTY_SUSPEND_THRESHOLD', 20);
}

if (!defined('PENALTY_SUSPEND_DAYS')) {
    define('PENALTY_SUSPEND_DAYS', 7);
}

if (!function_exists('kelas_normalize_key')) {
    function kelas_normalize_key($kelas)
    {
        $clean = trim((string)$kelas);
        $clean = preg_replace('/\s+/', ' ', $clean);
        return strtoupper($clean);
    }
}

if (!function_exists('kelas_penalty_get')) {
    function kelas_penalty_get($conn, $kelas)
    {
        ensure_peminjaman_schema($conn);
        $kelasKey = kelas_normalize_key($kelas);
        if ($kelasKey === '') {
            return null;
        }

        return db_fetch_one(
            $conn,
            "SELECT id, kelas_key, kelas_label, points, suspended_until
             FROM class_penalties
             WHERE kelas_key = ? LIMIT 1",
            "s",
            [$kelasKey]
        );
    }
}

if (!function_exists('kelas_penalty_is_blocked')) {
    function kelas_penalty_is_blocked($conn, $kelas)
    {
        $row = kelas_penalty_get($conn, $kelas);
        if (!$row) {
            return [
                'blocked' => false,
                'points' => 0,
                'suspended_until' => null,
            ];
        }

        $until = $row['suspended_until'] ?? null;
        $blocked = false;
        if (!empty($until)) {
            $blocked = (strtotime($until) > time());
        }

        return [
            'blocked' => $blocked,
            'points' => (int)($row['points'] ?? 0),
            'suspended_until' => $until,
        ];
    }
}

if (!function_exists('kelas_penalty_add_points')) {
    function kelas_penalty_add_points($conn, $kelas, $points)
    {
        ensure_peminjaman_schema($conn);
        $kelasKey = kelas_normalize_key($kelas);
        if ($kelasKey === '' || $points <= 0) {
            return false;
        }

        $current = kelas_penalty_get($conn, $kelasKey);
        if (!$current) {
            db_exec(
                $conn,
                "INSERT INTO class_penalties (kelas_key, kelas_label, points, last_violation_at)
                 VALUES (?, ?, 0, NOW())",
                "ss",
                [$kelasKey, $kelas]
            );
            $current = kelas_penalty_get($conn, $kelasKey);
        }

        if (!$current) {
            return false;
        }

        $newPoints = (int)$current['points'] + (int)$points;
        $currentSuspend = $current['suspended_until'] ?? null;
        $nextSuspend = $currentSuspend;

        if ($newPoints >= PENALTY_SUSPEND_THRESHOLD) {
            $candidate = date('Y-m-d H:i:s', strtotime('+' . PENALTY_SUSPEND_DAYS . ' days'));
            if (empty($currentSuspend) || strtotime($candidate) > strtotime($currentSuspend)) {
                $nextSuspend = $candidate;
            }
        }

        return db_exec(
            $conn,
            "UPDATE class_penalties
             SET kelas_label = ?, points = ?, suspended_until = ?, last_violation_at = NOW()
             WHERE id = ?",
            "sisi",
            [$kelas, $newPoints, $nextSuspend, (int)$current['id']]
        );
    }
}

if (!function_exists('apply_overdue_penalties')) {
    function apply_overdue_penalties($conn)
    {
        ensure_peminjaman_schema($conn);
        $overdues = mysqli_query(
            $conn,
            "SELECT id, kelas
             FROM peminjaman
             WHERE status = 'D'
               AND due_at IS NOT NULL
               AND due_at < NOW()
               AND overdue_penalty_applied = 0"
        );

        if (!$overdues) {
            return 0;
        }

        $applied = 0;
        while ($row = mysqli_fetch_assoc($overdues)) {
            $kelas = trim((string)($row['kelas'] ?? ''));
            if ($kelas !== '') {
                kelas_penalty_add_points($conn, $kelas, PENALTY_POINTS_OVERDUE);
            }
            db_exec(
                $conn,
                "UPDATE peminjaman SET overdue_penalty_applied = 1 WHERE id = ?",
                "i",
                [(int)$row['id']]
            );
            $applied++;
        }

        return $applied;
    }
}
