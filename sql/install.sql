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


INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES('/^Undefined array key ([0-9]+)$/', 'Undefined array key #number');

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES('/^Undefined offset: ([0-9]+)$/', 'Undefined offset: #number');

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES('/^Implicit conversion from float ([0-9.]+) to int loses precision$/', 'Implicit conversion from float to int loses precision');

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES('/^filemtime\\(\\): stat failed for .*$/', 'filemtime: stat failed for file');

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES ('/^SmartyException: Invalid compiled template for .*$/', 'SmartyException: Invalid compiled template');

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^ThirtyBeesDatabaseException: Duplicate entry '([^']+)' for key '([^']+)'/", "ThirtyBeesDatabaseException: Duplicate key value");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^Fatal Error: Allowed memory size of ([0-9]+) bytes exhausted \\(tried to allocate ([0-9]+) bytes\\)/", "Fatal Error: memory exhausted");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^include.*: *Failed.*/", "include: Failed to open file");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^imagejpeg.*failed to open stream: No such file or directory.*/", "imagejpeg: failed to open stream: No such file or directory");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^rmdir.*Directory not empty.*/", "rmdir(): Directory not empty");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^Undefined array key.*/",  "Undefined array key");

INSERT INTO PREFIX_collectlogs_convert_message(`search`, `replace`)
VALUES("/^unlink.*No such file or directory.*/", "unlink(): No such file or directory");
