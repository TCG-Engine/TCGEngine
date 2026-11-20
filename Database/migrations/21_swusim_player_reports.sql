-- Petranaki player reports — Report/Block Player (docs/superpowers/specs/2026-10-27-saved-chat-and-player-reports-design.md).
-- One row per report; a later report by the same reporter in the same match is a FOLLOW-UP on that row, not a new
-- one. The report itself lives on disk first (SWUSim/PlayerReports/R-<n>/: report.json + a frozen copy of the chat),
-- so a report survives a database failure; the viewer reconciles disk-only reports into this table. reportNumber
-- comes from the store's locked counter file, not AUTO_INCREMENT, for the same reason. clientToken is the sha1 of
-- the browser's one-time token, so a retried submission is one report.
--
-- Apply to the SWUSim app database only (local docker: swusim; prod: petranaki.net). The code checks the table
-- exists first, so until it runs reports are saved to disk only. Idempotent.

CREATE TABLE IF NOT EXISTS `swusim_player_reports` (
  `reportNumber`     int(11)       NOT NULL,              -- R-<n>
  `createdAt`        int(11)       NOT NULL,              -- unix time
  `updatedAt`        int(11)       NOT NULL,              -- last follow-up / status change
  `reporterId`       int(11)       NOT NULL,
  `reporterName`     varchar(64)   NOT NULL DEFAULT '',
  `reporterSeat`     tinyint(4)    NOT NULL DEFAULT 0,
  `reportedId`       int(11)       NOT NULL,
  `reportedName`     varchar(64)   NOT NULL DEFAULT '',
  `reportedSeat`     tinyint(4)    NOT NULL DEFAULT 0,
  `gameName`         varchar(32)   NOT NULL DEFAULT '',
  `matchId`          varchar(64)   NOT NULL DEFAULT '',
  `lobbyId`          varchar(64)   NOT NULL DEFAULT '',
  `format`           varchar(64)   NOT NULL DEFAULT '',
  `note`             text          NOT NULL,
  `followups`        tinyint(4)    NOT NULL DEFAULT 0,
  `status`           varchar(16)   NOT NULL DEFAULT 'open',
  `resolutionNote`   varchar(1000) NOT NULL DEFAULT '',
  `clientToken`      char(40)      NOT NULL,
  `clientIp`         varchar(64)   NOT NULL DEFAULT '',
  PRIMARY KEY (`reportNumber`),
  UNIQUE KEY `clientToken` (`clientToken`),
  KEY `reporter_time` (`reporterId`, `createdAt`),
  KEY `ip_time` (`clientIp`, `createdAt`),
  KEY `status_time` (`status`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
