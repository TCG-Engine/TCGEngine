-- 11_discord_oauth_users.sql
-- Enables Discord sign-in on an EXISTING installation. A Discord account is passwordless, so
-- `users.usersPwd` must be nullable; and one Discord identity must map to at most one account.
--
-- Replaces the old standalone `Database/discord_oauth_migration.sql`, which lived outside this
-- directory and was therefore invisible to the deploy runbook's "did the pull add a migration?"
-- check -- so a box could take the Discord code without the schema and fail signup with
-- "Column 'usersPwd' cannot be null".
--
-- Applies to EVERY app database that serves logins (`users` is the shared account table), not
-- only the stats DB. Not needed for a fresh install -- `Database/database.sql` already has both.
-- Idempotent: safe to re-run, and safe whether or not a `discordID` index already exists.

-- 1. Passwordless accounts. A NULL hash can never satisfy a login: every password path requires
--    is_string($hash) && $hash !== '' before password_verify().
ALTER TABLE `users` MODIFY `usersPwd` varchar(255) DEFAULT NULL;

-- 2. One Discord identity -> at most one account. Drops only a NON-unique `discordID` index (an
--    already-unique one is left alone), then adds the unique key only if no index remains.
--    /!\ The ADD fails if two rows already share a discordID. Check before applying:
--        SELECT discordID, COUNT(*) c FROM users
--         WHERE discordID IS NOT NULL AND discordID <> '' GROUP BY discordID HAVING c > 1;
SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
            AND INDEX_NAME = 'discordID' AND NON_UNIQUE = 1),
  'ALTER TABLE `users` DROP INDEX `discordID`',
  'DO 0');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  EXISTS(SELECT 1 FROM information_schema.STATISTICS
          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
            AND INDEX_NAME = 'discordID'),
  'DO 0',
  'ALTER TABLE `users` ADD UNIQUE KEY `discordID` (`discordID`)');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
