-- Daily Quests: definitions (tunable in Housekeeping) + per-player daily progress. Each quest tracks a
-- code the server increments; on reaching the goal the player claims reward_credits (once per day).
CREATE TABLE IF NOT EXISTS `daily_quests` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(48) NOT NULL,
  `name` VARCHAR(96) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `goal` INT NOT NULL DEFAULT 1,
  `reward_credits` INT NOT NULL DEFAULT 10,
  `enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dq_code` (`code`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `player_daily_quests` (
  `player_id` BIGINT NOT NULL,
  `quest_code` VARCHAR(48) NOT NULL,
  `quest_date` DATE NOT NULL,
  `progress` INT NOT NULL DEFAULT 0,
  `claimed` TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`player_id`, `quest_code`, `quest_date`)
) ENGINE=InnoDB;

INSERT IGNORE INTO `daily_quests` (`code`,`name`,`description`,`goal`,`reward_credits`,`sort_order`) VALUES
  ('visit_rooms',   'Explorer', 'Visit 3 different rooms.',        3, 10, 1),
  ('room_visitors', 'Host',     'Get 3 visitors to your rooms.',   3, 10, 2),
  ('buy_item',      'Shopper',  'Buy 1 item from a shop.',         1, 10, 3);
