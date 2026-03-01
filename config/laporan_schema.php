<?php

if (!function_exists('ensure_laporan_schema')) {
    function ensure_laporan_schema($conn)
    {
        mysqli_query($conn, "
            CREATE TABLE IF NOT EXISTS laporan (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                kelas VARCHAR(50) NOT NULL,
                kategori VARCHAR(20) NOT NULL,
                lokasi VARCHAR(100) NOT NULL,
                deskripsi TEXT NOT NULL,
                no_telp VARCHAR(25) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'Baru',
                tanggal_lapor DATE NOT NULL
            )
        ");

        $col = mysqli_query($conn, "SHOW COLUMNS FROM laporan LIKE 'no_telp'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE laporan ADD COLUMN no_telp VARCHAR(25) NULL AFTER deskripsi");
        }
    }
}
