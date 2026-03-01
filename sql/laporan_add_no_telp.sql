-- Add WhatsApp number field for laporan notifications
ALTER TABLE `laporan`
  ADD COLUMN `no_telp` VARCHAR(25) NULL AFTER `deskripsi`;
