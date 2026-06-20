-- Support / ticket system: private back-and-forth conversations between a player and staff. Users open
-- tickets and reply; staff (in-client admins or Housekeeping) reply, close, and can restrict abusers.
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` BIGINT NOT NULL,
  `subject` VARCHAR(120) NOT NULL,
  `category` VARCHAR(40) NOT NULL DEFAULT 'general',
  `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
  `closed_by` VARCHAR(16) DEFAULT NULL,
  `created_at` DATETIME(6) NOT NULL,
  `updated_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_st_player` (`player_id`),
  KEY `ix_st_status` (`status`),
  KEY `ix_st_updated` (`updated_at`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `support_ticket_messages` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ticket_id` INT NOT NULL,
  `sender_id` BIGINT NOT NULL,
  `sender_name` VARCHAR(64) NOT NULL,
  `is_staff` TINYINT(1) NOT NULL DEFAULT 0,
  `body` TEXT NOT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_stm_ticket` (`ticket_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `support_ticket_bans` (
  `player_id` BIGINT NOT NULL,
  `reason` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB;

-- Tracks whether a player has seen the first-time guide popup (so it shows once for new users).
CREATE TABLE IF NOT EXISTS `player_guide_seen` (
  `player_id` BIGINT NOT NULL,
  `seen_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB;
