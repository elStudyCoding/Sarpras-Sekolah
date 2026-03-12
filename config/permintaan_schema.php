<?php

if (!function_exists('ensure_permintaan_schema')) {
    function ensure_permintaan_schema($conn)
    {
        static $ready = false;
        if ($ready) {
            return true;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS permintaan_barang (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                kelas VARCHAR(50) NULL,
                kategori VARCHAR(40) NOT NULL DEFAULT 'Alat Tulis',
                barang VARCHAR(120) NOT NULL,
                jumlah INT NOT NULL,
                jam_mapel VARCHAR(80) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Pending',
                approved_by INT NULL,
                approved_at DATETIME NULL,
                tanggal_input DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY idx_tanggal_input (tanggal_input),
                CONSTRAINT fk_permintaan_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON UPDATE CASCADE
                    ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ";

        mysqli_query($conn, $sql);
        $col = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_barang LIKE 'kelas'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE permintaan_barang ADD COLUMN kelas VARCHAR(50) NULL AFTER user_id");
        }
        $col = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_barang LIKE 'kategori'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE permintaan_barang ADD COLUMN kategori VARCHAR(40) NOT NULL DEFAULT 'Alat Tulis' AFTER kelas");
        }
        $col = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_barang LIKE 'status'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE permintaan_barang ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'Pending' AFTER jam_mapel");
        }
        $col = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_barang LIKE 'approved_by'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE permintaan_barang ADD COLUMN approved_by INT NULL AFTER status");
        }
        $col = mysqli_query($conn, "SHOW COLUMNS FROM permintaan_barang LIKE 'approved_at'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE permintaan_barang ADD COLUMN approved_at DATETIME NULL AFTER approved_by");
        }
        $ready = true;
        return true;
    }
}
