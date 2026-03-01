<?php

if (!function_exists('db_stmt_prepare')) {
    function db_stmt_prepare($conn, $sql)
    {
        $stmt = mysqli_prepare($conn, $sql);
        return $stmt ?: false;
    }
}

if (!function_exists('db_stmt_bind')) {
    function db_stmt_bind($stmt, $types, array $params)
    {
        if ($types === '' || empty($params)) {
            return true;
        }

        $bind = [$stmt, $types];
        foreach ($params as $key => $value) {
            $bind[] = &$params[$key];
        }

        return call_user_func_array('mysqli_stmt_bind_param', $bind);
    }
}

if (!function_exists('db_stmt_execute')) {
    function db_stmt_execute($conn, $sql, $types = '', array $params = [])
    {
        $stmt = db_stmt_prepare($conn, $sql);
        if ($stmt === false) {
            return false;
        }

        if (!db_stmt_bind($stmt, $types, $params)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }

        return $stmt;
    }
}

if (!function_exists('db_fetch_one')) {
    function db_fetch_one($conn, $sql, $types = '', array $params = [])
    {
        $stmt = db_stmt_execute($conn, $sql, $types, $params);
        if ($stmt === false) {
            return null;
        }

        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }
}

if (!function_exists('db_exec')) {
    function db_exec($conn, $sql, $types = '', array $params = [])
    {
        $stmt = db_stmt_execute($conn, $sql, $types, $params);
        if ($stmt === false) {
            return false;
        }

        $insertId = mysqli_insert_id($conn);
        $affectedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);

        return [
            'insert_id' => $insertId,
            'affected_rows' => $affectedRows,
        ];
    }
}
