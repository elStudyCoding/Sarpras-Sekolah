<?php

if (!function_exists('get_public_actor_id')) {
    function get_public_actor_id($conn)
    {
        $publicUsername = 'public_school';
        $publicName = 'Layanan Sarpras Sekolah';

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, "s", $publicUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($row && isset($row['id'])) {
            return (int)$row['id'];
        }

        $randomPassword = bin2hex(random_bytes(12));
        $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
        $role = 'user';

        $insert = mysqli_prepare($conn, "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, ?)");
        if (!$insert) {
            return 0;
        }
        mysqli_stmt_bind_param($insert, "ssss", $publicName, $publicUsername, $hashedPassword, $role);
        $ok = mysqli_stmt_execute($insert);
        mysqli_stmt_close($insert);

        if ($ok) {
            return (int)mysqli_insert_id($conn);
        }

        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            return 0;
        }
        mysqli_stmt_bind_param($stmt, "s", $publicUsername);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        return $row && isset($row['id']) ? (int)$row['id'] : 0;
    }
}
