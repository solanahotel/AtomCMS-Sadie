-- Marketplace core schema (Phase 0). Charset/collation intentionally omitted so each table inherits
-- the database default (works on the live MySQL 9.6 AND a fresh MariaDB install). All cross-table
-- references are integer FKs, so no collation matching is required.

-- A single listing across all three sections. The credit-item Exchange uses quantity > 1 +
-- unit_credit_value (per-item credits) so the UI can show the live total = quantity * unit_credit_value.
CREATE TABLE IF NOT EXISTS `marketplace_listings` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `seller_id` BIGINT NOT NULL,
  `buyer_id` BIGINT DEFAULT NULL,
  `section` VARCHAR(16) NOT NULL,                 -- item_market | auction | credit_exchange
  `status` VARCHAR(16) NOT NULL DEFAULT 'active', -- active | pending | sold | cancelled | expired | failed
  `furniture_item_id` INT DEFAULT NULL,           -- listed item definition (display / origin badge / filter)
  `quantity` INT NOT NULL DEFAULT 1,              -- # of item instances escrowed (>1 for credit-item bundles)
  `unit_credit_value` INT DEFAULT NULL,           -- credit_exchange: per-item credit value (e.g. 50)
  `total_credit_value` INT DEFAULT NULL,          -- quantity * unit_credit_value (display)
  `price_credits` INT DEFAULT NULL,               -- item_market fixed price / auction current price
  `price_hotel` DECIMAL(20,9) DEFAULT NULL,       -- credit_exchange: $HOTEL price for the whole bundle
  `start_price_credits` INT DEFAULT NULL,         -- auction starting price
  `highest_bid` INT DEFAULT NULL,                 -- auction
  `highest_bidder_id` BIGINT DEFAULT NULL,        -- auction
  `duration_hours` INT DEFAULT NULL,              -- auction (6/12/24/48)
  `locked_until` DATETIME(6) DEFAULT NULL,        -- credit_exchange pending-purchase lock timeout
  `created_at` DATETIME(6) NOT NULL,
  `updated_at` DATETIME(6) DEFAULT NULL,
  `expires_at` DATETIME(6) DEFAULT NULL,
  `completed_at` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ml_seller` (`seller_id`),
  KEY `ix_ml_section_status` (`section`, `status`),
  KEY `ix_ml_furniture` (`furniture_item_id`),
  CONSTRAINT `fk_ml_seller` FOREIGN KEY (`seller_id`) REFERENCES `players` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ml_buyer` FOREIGN KEY (`buyer_id`) REFERENCES `players` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Escrow held by a listing, stored as item SNAPSHOTS (not a FK). When an item is listed it is removed
-- from the seller's inventory (its player_furniture_items row is deleted, gift-system style) and its
-- state snapshotted here, so it cannot reappear on relog or be placed/traded while listed. On sale a
-- fresh row is created for the buyer; on cancel/expire a fresh row is recreated for the seller.
CREATE TABLE IF NOT EXISTS `marketplace_listing_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `listing_id` INT NOT NULL,
  `furniture_item_id` INT NOT NULL,      -- furniture definition
  `limited_data` LONGTEXT DEFAULT NULL,  -- preserved limited/edition state
  `meta_data` LONGTEXT DEFAULT NULL,     -- preserved metadata
  PRIMARY KEY (`id`),
  KEY `ix_mli_listing` (`listing_id`),
  CONSTRAINT `fk_mli_listing` FOREIGN KEY (`listing_id`) REFERENCES `marketplace_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Full traceability of every marketplace action (per spec). No FK -> logs survive listing deletion.
CREATE TABLE IF NOT EXISTS `marketplace_logs` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `event_type` VARCHAR(24) NOT NULL,              -- list|buy|cancel|expire|bid|outbid|auction_win
  `section` VARCHAR(16) NOT NULL,
  `listing_id` INT DEFAULT NULL,
  `seller_id` BIGINT DEFAULT NULL,
  `buyer_id` BIGINT DEFAULT NULL,
  `furniture_item_id` INT DEFAULT NULL,
  `quantity` INT DEFAULT NULL,
  `credit_amount` INT DEFAULT NULL,
  `hotel_amount` DECIMAL(20,9) DEFAULT NULL,
  `fee_amount` DECIMAL(20,9) DEFAULT NULL,
  `fee_currency` VARCHAR(8) DEFAULT NULL,         -- credits | hotel
  `seller_ip` VARCHAR(45) DEFAULT NULL,
  `buyer_ip` VARCHAR(45) DEFAULT NULL,
  `tx_signature` VARCHAR(128) DEFAULT NULL,
  `status` VARCHAR(16) DEFAULT NULL,
  `created_at` DATETIME(6) NOT NULL,
  `completed_at` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_mlog_seller` (`seller_id`),
  KEY `ix_mlog_buyer` (`buyer_id`),
  KEY `ix_mlog_listing` (`listing_id`),
  KEY `ix_mlog_event` (`event_type`)
) ENGINE=InnoDB;

-- Persistent, offline-safe inbox (D3). Used for sale/auction/outbid notifications.
CREATE TABLE IF NOT EXISTS `player_inbox` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `player_id` BIGINT NOT NULL,
  `category` VARCHAR(24) NOT NULL DEFAULT 'marketplace', -- marketplace | auction | system
  `title` VARCHAR(128) NOT NULL,
  `body` TEXT NOT NULL,
  `payload` JSON DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME(6) NOT NULL,
  `read_at` DATETIME(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_inbox_player_read` (`player_id`, `is_read`),
  CONSTRAINT `fk_inbox_player` FOREIGN KEY (`player_id`) REFERENCES `players` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;
