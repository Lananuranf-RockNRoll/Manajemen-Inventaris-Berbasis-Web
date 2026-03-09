-- Auto-run saat MySQL container pertama kali start
CREATE DATABASE IF NOT EXISTS `inventory_system` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `inventory_system`.* TO 'invensys'@'%';
FLUSH PRIVILEGES;
