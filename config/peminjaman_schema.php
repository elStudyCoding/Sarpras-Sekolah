<?php

if (!function_exists('ensure_peminjaman_schema')) {
    function ensure_peminjaman_schema($conn)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $createPenaltyTable = "
            CREATE TABLE IF NOT EXISTS class_penalties (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kelas_key VARCHAR(80) NOT NULL UNIQUE,
                kelas_label VARCHAR(80) NOT NULL,
                points INT NOT NULL DEFAULT 0,
                suspended_until DATETIME NULL,
                last_violation_at DATETIME NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ";
        mysqli_query($conn, $createPenaltyTable);

        $columnsToAdd = [
            'no_telp' => "ALTER TABLE peminjaman ADD COLUMN no_telp VARCHAR(25) NULL AFTER kelas",
            'due_at' => "ALTER TABLE peminjaman ADD COLUMN due_at DATETIME NULL AFTER tanggal_pinjam",
            'reminder_sent_at' => "ALTER TABLE peminjaman ADD COLUMN reminder_sent_at DATETIME NULL AFTER due_at",
            'overdue_penalty_applied' => "ALTER TABLE peminjaman ADD COLUMN overdue_penalty_applied TINYINT(1) NOT NULL DEFAULT 0 AFTER reminder_sent_at",
        ];

        foreach ($columnsToAdd as $columnName => $sql) {
            $col = mysqli_query($conn, "SHOW COLUMNS FROM peminjaman LIKE '" . mysqli_real_escape_string($conn, $columnName) . "'");
            if ($col && mysqli_num_rows($col) === 0) {
                mysqli_query($conn, $sql);
            }
        }

        $idxLoan = mysqli_query($conn, "SHOW INDEX FROM peminjaman WHERE Key_name = 'idx_peminjaman_status_due'");
        if ($idxLoan && mysqli_num_rows($idxLoan) === 0) {
            mysqli_query($conn, "CREATE INDEX idx_peminjaman_status_due ON peminjaman(status, due_at)");
        }

        $idxPenalty = mysqli_query($conn, "SHOW INDEX FROM class_penalties WHERE Key_name = 'idx_class_penalties_suspend'");
        if ($idxPenalty && mysqli_num_rows($idxPenalty) === 0) {
            mysqli_query($conn, "CREATE INDEX idx_class_penalties_suspend ON class_penalties(suspended_until)");
        }

        $ready = true;
        return true;
    }
}
