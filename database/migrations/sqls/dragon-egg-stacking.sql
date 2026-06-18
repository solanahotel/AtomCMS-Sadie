-- Make Dragon Eggs (spyro / nft_spyro) stackable on each other.
-- can_stack=1 lets items be placed on top; stack_height=0.32 sets how high each
-- stacked egg sits above the one below. Idempotent. Requires an emulator restart
-- (furni definitions are read on room load).

UPDATE furniture_items
SET can_stack = 1,
    stack_height = 0.32
WHERE asset_name IN ('spyro', 'nft_spyro');
