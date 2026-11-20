-- 24_users_datecreated_backfill_swu.sql
-- Backfills `users.dateCreated` for accounts that predate it: 2026-11-08T00:00:00.000Z, the day tracking began.
-- Read it as "created on or before 2026-11-08", not as a real signup date.
--
-- Apply to the SWUSim (petranaki.net) and SWUStats (swustats.net) app databases ONLY -- other sims keep NULL.
-- Requires 23_users_datecreated.sql; does nothing where the column is missing.
-- Idempotent: only NULL rows are written, so it never overwrites a real stamp from the signup code, and re-running it
-- is a no-op.

SET @s = IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'users' AND column_name = 'dateCreated') = 1,
    'UPDATE `users` SET `dateCreated` = ''2026-11-08 00:00:00.000'' WHERE `dateCreated` IS NULL', 'SELECT 1');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
