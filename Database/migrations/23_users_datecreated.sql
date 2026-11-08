-- 23_users_datecreated.sql
-- Adds `users.dateCreated`: when the account was created, in UTC, to the millisecond (DATETIME(3)).
--
-- NULL means "not known" -- every account that predates this column. There is deliberately no DEFAULT: a
-- DEFAULT CURRENT_TIMESTAMP would stamp every EXISTING row with the moment this ALTER ran, which looks like a real
-- creation date and is not. New accounts are stamped by the signup code instead (StampUserDateCreated in
-- Database/functions.inc.php), which checks DBColumnExists() first -- so a box that has not run this keeps working
-- and simply records nothing.
--
-- Safe for EVERY app database that serves logins (`users` is the shared account table); it changes nothing for a
-- sim that never reads the column. Idempotent: the ALTER runs only when the column is missing.
-- The SWUSim/SWUStats backfill of existing accounts is 24_users_datecreated_backfill_swu.sql.

SET @s = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'dateCreated') = 0,
    'ALTER TABLE `users` ADD COLUMN `dateCreated` datetime(3) DEFAULT NULL AFTER `lastLoggedIP`', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
