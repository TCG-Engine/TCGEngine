-- meleetournamentmatchup carries only PRIMARY(matchupID). Every lookup by deck — the matchup
-- self-join that audits win/loss attribution consistency, and the delete-by-tournament path in
-- zzSWUDeckMatrix.php — therefore full-scans the table (~69k rows, so ~4.7 billion row
-- comparisons for the self-join).
--
-- MySQL 8+/9 masks this with a hash join (~0.4s locally). MariaDB, which prod runs, falls back
-- to a block nested loop and the request hangs until it times out. These two indexes make the
-- join an indexed lookup on both engines.
--
-- Safe and additive: no data change, no column change. On a table this size it applies in
-- well under a second.

ALTER TABLE `meleetournamentmatchup`
  ADD INDEX `idx_mtm_player` (`player`),
  ADD INDEX `idx_mtm_opponent` (`opponent`);
