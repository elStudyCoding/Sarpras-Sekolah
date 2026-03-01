-- Tabel permintaan barang habis pakai
-- Input hanya: barang, jumlah, jam_mapel

CREATE TABLE IF NOT EXISTS `permintaan_barang` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `kelas` VARCHAR(50) NULL,
  `barang` VARCHAR(120) NOT NULL,
  `jumlah` INT NOT NULL,
  `jam_mapel` VARCHAR(80) NOT NULL,
  `tanggal_input` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_tanggal_input` (`tanggal_input`),
  CONSTRAINT `fk_permintaan_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
