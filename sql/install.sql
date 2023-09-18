CREATE TABLE IF NOT EXISTS `PREFIX_collectlogs_logs` (
    `id_collectlogs_logs` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `date_add` DATETIME NOT NULL,
    `uid` CHAR(32) NOT NULL,
    `type` VARCHAR(128),
    `severity` TINYINT(1) UNSIGNED,
    `file` VARCHAR(512),
    `line` INT(11) UNSIGNED,
    `real_file` VARCHAR(512),
    `real_line` INT(11) UNSIGNED,
    `generic_message` VARCHAR(512),
    `sample_message` VARCHAR(512),
    PRIMARY KEY (`id_collectlogs_logs`),
    UNIQUE KEY `uid` (`uid`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_collectlogs_extra` (
    `id_collectlogs_extra` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_collectlogs_logs` INT(11) UNSIGNED NOT NULL,
    `label` VARCHAR (200),
    `content` TEXT,
    PRIMARY KEY (`id_collectlogs_extra`),
    FOREIGN KEY `clle_log` (`id_collectlogs_logs`) REFERENCES `PREFIX_collectlogs_logs`(`id_collectlogs_logs`) ON DELETE CASCADE
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_collectlogs_convert_message` (
    `id_collectlogs_convert_message` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_remote` INT(11) UNSIGNED NULL,
    `search` VARCHAR(512) NOT NULL,
    `replace` VARCHAR(512) NOT NULL,
    PRIMARY KEY (`id_collectlogs_convert_message`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;

CREATE TABLE IF NOT EXISTS `PREFIX_collectlogs_stats` (
    `id_collectlogs_stats` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_collectlogs_logs` INT(11) UNSIGNED NOT NULL,
    `dimension` CHAR(10) NOT NULL,
    `count` INT(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_collectlogs_stats`),
    UNIQUE KEY `dimension` (`id_collectlogs_logs`, `dimension`),
    FOREIGN KEY `clls_log` (`id_collectlogs_logs`) REFERENCES `PREFIX_collectlogs_logs`(`id_collectlogs_logs`) ON DELETE CASCADE
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE COLLATE=COLLATE_TYPE;