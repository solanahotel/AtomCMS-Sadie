-- Admin permissions + role assignment
-- ----------------------------------------------------------------------------
-- The emulator gates admin powers via permissions -> roles -> player_role, all
-- AutoInclude'd by the model. default.sql ships these rows but they may not be
-- loaded (same gap as hand_items). This seed loads the canonical permission set
-- and grants the 'dev' account the Admin role (id 6), which includes
-- any_room_rights + any_room_owner -> Owner-level control in EVERY room, so the
-- client's standard furniture pick-up/move buttons work on any item anywhere.
-- Idempotent. Re-login (or restart emulator) for changes to take effect.
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `permissions` (`id`, `name`) VALUES
	(1, 'moderator'),
	(2, 'command_shutdown'),
	(3, 'command_hotel_alert'),
	(4, 'command_user_info'),
	(5, 'command_kick'),
	(6, 'command_kick_all'),
	(7, 'command_unload'),
	(8, 'any_room_owner'),
	(9, 'any_room_rights');
INSERT IGNORE INTO `roles` (`id`, `name`) VALUES
	(1, 'User'),
	(5, 'Moderator'),
	(6, 'Admin');
INSERT IGNORE INTO `roles_permissions` (`permission_id`, `role_id`) VALUES
	(1, 5),
	(3, 5),
	(4, 5),
	(5, 5),
	(6, 5),
	(7, 5),
	(8, 5),
	(9, 5),
	(1, 6),
	(2, 6),
	(3, 6),
	(4, 6),
	(5, 6),
	(6, 6),
	(7, 6),
	(8, 6),
	(9, 6);
-- Grant the admin (matched by username) the Admin role:
INSERT IGNORE INTO `player_role` (`role_id`, `player_id`) SELECT 6, id FROM players WHERE username = 'dev';
