-- Anti-farming: per-login IP capture for alt-cluster / Sybil detection (surfaced in the admin feed).
CREATE TABLE IF NOT EXISTS `player_login_ips` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` BIGINT NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pli_player` (`player_id`),
  KEY `ix_pli_ip` (`ip_address`),
  KEY `ix_pli_created` (`created_at`)
) ENGINE=InnoDB;
