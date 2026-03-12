<?php

if (!function_exists('ensure_barang_schema')) {
    function ensure_barang_schema($conn)
    {
        $col = mysqli_query($conn, "SHOW COLUMNS FROM barang LIKE 'kategori'");
        if ($col && mysqli_num_rows($col) === 0) {
            mysqli_query($conn, "ALTER TABLE barang ADD COLUMN kategori VARCHAR(80) NULL AFTER nama_barang");
        }
    }
}
