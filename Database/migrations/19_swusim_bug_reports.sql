-- Petranaki's own SWUSim bug reports (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md).
-- One row per report. The report itself lives on disk first (SWUSim/BugReports/P-<n>/), so a report survives a
-- database failure; the viewer reconciles disk-only reports into this table. reportNumber comes from the store's
-- locked counter file, not AUTO_INCREMENT, for the same reason. clientToken is the sha1 of the browser's one-time
-- token, so a retried submission is one report.
--
-- Apply to the SWUSim app database only (local docker: swusim; prod: petranaki.net). The code checks
-- DBTableExists() first, so until it runs reports are saved to disk only. Idempotent.

CREATE TABLE IF NOT EXISTS `swusim_bug_reports` (
  `reportNumber`     int(11)       NOT NULL,              -- P-<n>
  `createdAt`        int(11)       NOT NULL,              -- unix time
  `gameName`         varchar(32)   NOT NULL DEFAULT '',
  `playerID`         varchar(8)    NOT NULL DEFAULT '',
  `verified`         tinyint(1)    NOT NULL DEFAULT 0,    -- game + seat key checked out
  `unverifiedReason` varchar(255)  NOT NULL DEFAULT '',
  `reporter`         varchar(64)   NOT NULL DEFAULT '',
  `accountId`        int(11)       DEFAULT NULL,
  `accountName`      varchar(64)   DEFAULT NULL,
  `description`      text          NOT NULL,
  `status`           varchar(16)   NOT NULL DEFAULT 'open',
  `resolutionNote`   varchar(1000) NOT NULL DEFAULT '',
  `gamestateHash`    char(64)      NOT NULL DEFAULT '',
  `hasSnapshot`      tinyint(1)    NOT NULL DEFAULT 0,
  `clientToken`      char(40)      NOT NULL,
  `clientIp`         varchar(64)   NOT NULL DEFAULT '',
  PRIMARY KEY (`reportNumber`),
  UNIQUE KEY `clientToken` (`clientToken`),
  KEY `seat_time` (`gameName`, `playerID`, `createdAt`),
  KEY `ip_time` (`clientIp`, `createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
