-- Penalty points per kelas + suspend + reminder WA
-- Jalankan di database: sarpras

CREATE TABLE IF NOT EXISTS `class_penalties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kelas_key` VARCHAR(80) NOT NULL UNIQUE,
  `kelas_label` VARCHAR(80) NOT NULL,
  `points` INT NOT NULL DEFAULT 0,
  `suspended_until` DATETIME NULL,
  `last_violation_at` DATETIME NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `peminjaman` ADD COLUMN IF NOT EXISTS `no_telp` VARCHAR(25) NULL AFTER `kelas`;
ALTER TABLE `peminjaman` ADD COLUMN IF NOT EXISTS `due_at` DATETIME NULL AFTER `tanggal_pinjam`;
ALTER TABLE `peminjaman` ADD COLUMN IF NOT EXISTS `reminder_sent_at` DATETIME NULL AFTER `due_at`;
ALTER TABLE `peminjaman` ADD COLUMN IF NOT EXISTS `overdue_penalty_applied` TINYINT(1) NOT NULL DEFAULT 0 AFTER `reminder_sent_at`;

CREATE INDEX `idx_peminjaman_status_due` ON `peminjaman` (`status`, `due_at`);
CREATE INDEX `idx_class_penalties_suspend` ON `class_penalties` (`suspended_until`);
