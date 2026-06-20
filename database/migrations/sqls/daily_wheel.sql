-- Daily Wheel: one free spin per player per day. UNIQUE(player_id, spun_on) enforces the daily limit
-- at the DB level (and doubles as the spin log). reward_amount = credits, or the granted item def id.
CREATE TABLE IF NOT EXISTS `player_wheel_spins` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` BIGINT NOT NULL,
  `spun_on` DATE NOT NULL,
  `reward_type` VARCHAR(32) NOT NULL,
  `reward_amount` INT NOT NULL DEFAULT 0,
  `reward_label` VARCHAR(96) NOT NULL,
  `is_member` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_player_day` (`player_id`, `spun_on`),
  KEY `ix_pws_player` (`player_id`)
) ENGINE=InnoDB;
