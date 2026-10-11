-- Petranaki ↔ SWUStats account link (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §1).
-- One row per Petranaki account linked to a SWUStats account. A table of its own rather than columns on
-- `users`: `users` is the shared account table every app DB carries, and the code gates on
-- DBTableExists('swustats_links'), so a box that has not run this keeps working with linking simply off.
--
-- Apply to the SWUSim app database only (local docker: swusim; prod: petranaki.net). Idempotent.

CREATE TABLE IF NOT EXISTS `swustats_links` (
  `usersId`          int(11)      NOT NULL,              -- Petranaki account (this DB's users.usersId)
  `swustatsUserId`   int(11)      NOT NULL,              -- SWUStats users.usersId, from userinfo.php
  `swustatsUsername` varchar(128) NOT NULL DEFAULT '',
  `accessToken`      varchar(255) NOT NULL,
  `refreshToken`     varchar(255) NOT NULL,              -- ROTATES on every refresh (SWUStats deletes the old one)
  `accessExpires`    int(11)      NOT NULL,              -- unix time
  `linkedAt`         int(11)      NOT NULL,
  PRIMARY KEY (`usersId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
