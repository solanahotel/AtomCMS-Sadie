-- Faucet infrastructure: daily caps (per faucet, reset by date), per-day uniqueness (unique visitors /
-- rooms), and login-streak tracking. All caps are enforced against player_faucet_earnings.

CREATE TABLE IF NOT EXISTS `player_faucet_earnings` (
  `player_id` BIGINT NOT NULL,
  `faucet` VARCHAR(32) NOT NULL,
  `earned_on` DATE NOT NULL,
  `amount` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`player_id`, `faucet`, `earned_on`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `player_faucet_unique` (
  `player_id` BIGINT NOT NULL,
  `kind` VARCHAR(32) NOT NULL,
  `ref_id` BIGINT NOT NULL,
  `logged_on` DATE NOT NULL,
  PRIMARY KEY (`player_id`, `kind`, `ref_id`, `logged_on`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `player_login_streaks` (
  `player_id` BIGINT NOT NULL,
  `last_login_date` DATE NOT NULL,
  `streak_days` INT NOT NULL DEFAULT 1,
  PRIMARY KEY (`player_id`)
) ENGINE=InnoDB;
