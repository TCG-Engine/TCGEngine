-- Glicko-2 ratings for rated queues — first user: SWUSim's Meta Premier (owner 2026-10-03),
-- docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §3. Named for the algorithm, not the format, so any
-- sim/format can rate into the same tables: ratings and results carry a `format` column (each format its own ladder).
--
-- Applies to every app database that serves SWUSim logins. Idempotent (CREATE TABLE IF NOT EXISTS). The code checks
-- DBTableExists() first, so a server that has not run this keeps working and simply does not rate matches.
--
-- glicko_ratings   — one row per account × format × match type (bo1/bo3) × season. Glicko-2 rating / RD / volatility.
-- glicko_results   — append-only log of rated matches; (matchId, matchCreatedAt) is the idempotency key (match ids
--                         come from a file counter that can reset, so the creation time disambiguates). Enough to
--                         replay every rating if a constant changes. rated = 0 marks a pregame abandon (a strike,
--                         ratings untouched).
-- glicko_penalties — abandon strikes and the rated-queue cooldown, per account.

CREATE TABLE IF NOT EXISTS `glicko_ratings` (
  `userId`      int(11)     NOT NULL,
  `format`      varchar(32) NOT NULL,   -- required, no default: the rated format id (e.g. 'metapremier')
  `queueType`   varchar(8)  NOT NULL,
  `season`      int(11)     NOT NULL DEFAULT 1,
  `rating`      double      NOT NULL DEFAULT 1500,
  `rd`          double      NOT NULL DEFAULT 350,
  `volatility`  double      NOT NULL DEFAULT 0.06,
  `games`       int(11)     NOT NULL DEFAULT 0,
  `wins`        int(11)     NOT NULL DEFAULT 0,
  `losses`      int(11)     NOT NULL DEFAULT 0,
  `abandons`    int(11)     NOT NULL DEFAULT 0,
  `lastRatedAt` int(11)     NULL,
  `updatedAt`   int(11)     NOT NULL,
  PRIMARY KEY (`userId`, `format`, `queueType`, `season`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `glicko_results` (
  `id`             int(11)     NOT NULL AUTO_INCREMENT,
  `matchId`        varchar(32) NOT NULL,
  `matchCreatedAt` int(11)     NOT NULL,
  `format`         varchar(32) NOT NULL,   -- required, no default
  `queueType`      varchar(8)  NOT NULL,
  `season`         int(11)     NOT NULL,
  `rated`          tinyint(1)  NOT NULL DEFAULT 1,
  `winnerUserId`   int(11)     NOT NULL,
  `loserUserId`    int(11)     NOT NULL,
  `outcome`        varchar(8)  NOT NULL,
  `wRatingBefore`  double      NOT NULL,
  `wRdBefore`      double      NOT NULL,
  `wVolBefore`     double      NOT NULL,
  `wRatingAfter`   double      NOT NULL,
  `wRdAfter`       double      NOT NULL,
  `wVolAfter`      double      NOT NULL,
  `lRatingBefore`  double      NOT NULL,
  `lRdBefore`      double      NOT NULL,
  `lVolBefore`     double      NOT NULL,
  `lRatingAfter`   double      NOT NULL,
  `lRdAfter`       double      NOT NULL,
  `lVolAfter`      double      NOT NULL,
  `penaltyApplied` double      NOT NULL DEFAULT 0,
  `ratedAt`        int(11)     NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_match` (`matchId`, `matchCreatedAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `glicko_penalties` (
  `userId`                  int(11) NOT NULL,
  `abandonStrikes`          int(11) NOT NULL DEFAULT 0,
  `cooldownUntil`           int(11) NOT NULL DEFAULT 0,
  `lastAbandonAt`           int(11) NULL,
  `cleanMatchesSinceStrike` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
