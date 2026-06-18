-- Wrapped contents for gift presents. The present itself is a normal player_furniture_items
-- row (a present_gen furni whose MetaData stays numeric so it renders); this table records
-- what's inside so opening it can hand over the right item. Removed when the present is opened.
CREATE TABLE IF NOT EXISTS player_gifts (
    present_item_id          BIGINT      NOT NULL PRIMARY KEY,
    base_furniture_item_id   BIGINT      NOT NULL,
    sender_id                BIGINT      NOT NULL,
    sender_name              VARCHAR(64) NOT NULL DEFAULT '',
    message                  TEXT,
    created_at               DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
);
