CREATE TABLE `jobs2` (
    `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `target` char(32) NOT NULL,
    `name` char(64) NOT NULL,
    `time_created` int(10) UNSIGNED NOT NULL DEFAULT 0,
    `time_started` int(10) UNSIGNED NOT NULL DEFAULT 0,
    `time_finished` int(10) UNSIGNED NOT NULL DEFAULT 0,
    `status` enum('waiting', 'manual', 'accepted', 'running', 'done', 'ignored') NOT NULL DEFAULT 'waiting',
    `result` enum('ok', 'fail') DEFAULT NULL,
    `return_code` tinyint(3) UNSIGNED DEFAULT NULL,
    `sig` char(10) DEFAULT NULL,
    `input` mediumtext NOT NULL,
    `stdout` mediumtext NOT NULL DEFAULT '',
    `stderr` mediumtext NOT NULL DEFAULT '',
    PRIMARY KEY (`id`),
    KEY `select_for_target_priority_idx` (`target`, `status`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;