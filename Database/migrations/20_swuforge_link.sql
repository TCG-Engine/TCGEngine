-- Petranaki ↔ SWU Forge: the account link (OAuth tokens) and the queue of finished games waiting to be delivered.
-- Tables of their own rather than columns on `users` (the shared account table every app DB carries), and the code
-- gates on DBTableExists('swuforge_links'), so a box that has not run this keeps working with the link simply off.
--
-- Apply to the SWUSim app database only (local docker: swusim; prod: petranaki.net). Idempotent.

CREATE TABLE IF NOT EXISTS `swuforge_links` (
  `usersId`       int(11)       NOT NULL,              -- Petranaki account (this DB's users.usersId)
  `profileName`   varchar(128)  NOT NULL DEFAULT '',   -- the SWU Forge profile games are saved to (/api/petranaki/me)
  `accessToken`   varchar(1024) NOT NULL,
  `refreshToken`  varchar(1024) NOT NULL,              -- ROTATES on every refresh (30 s grace on Forge's side)
  `accessExpires` int(11)       NOT NULL,              -- unix time
  `linkedAt`      int(11)       NOT NULL,
  `problem`       varchar(32)   NOT NULL DEFAULT '',   -- the last delivery's standing problem: '' | not-enabled (403) | no-profile (409)
  PRIMARY KEY (`usersId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One row per (account, game, seat): the game's all-seeing SWU-PGN file and what to send with it. A worker posts each
-- due row and retries 502/503/no-answer with backoff; Forge's re-delivery is idempotent on (game, profile).
CREATE TABLE IF NOT EXISTS `swuforge_deliveries` (
  `id`               int(11)      NOT NULL AUTO_INCREMENT,
  `usersId`          int(11)      NOT NULL,
  `gameName`         varchar(64)  NOT NULL,
  `seat`             tinyint(4)   NOT NULL,
  `pgn`              mediumtext   NOT NULL,
  `lobbyId`          varchar(200) NOT NULL DEFAULT '',   -- the raw match id (the same for every game of a Bo3)
  `deckName`         varchar(255) NOT NULL DEFAULT '',
  `opponentDeckName` varchar(255) NOT NULL DEFAULT '',
  `deckId`           varchar(64)  NOT NULL DEFAULT '',   -- a swuforge.com/decks/<id> import's id
  `status`           varchar(16)  NOT NULL DEFAULT 'pending',   -- pending | done | skipped | failed | unlinked
  `attempts`         int(11)      NOT NULL DEFAULT 0,
  `nextAttemptAt`    int(11)      NOT NULL DEFAULT 0,
  `lastHttp`         int(11)      NOT NULL DEFAULT 0,
  `lastError`        varchar(255) NOT NULL DEFAULT '',
  `createdAt`        int(11)      NOT NULL,
  `updatedAt`        int(11)      NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `game_seat_user` (`gameName`, `seat`, `usersId`),
  KEY `due` (`status`, `nextAttemptAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
