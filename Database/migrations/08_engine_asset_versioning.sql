-- Promote per-version W/L aggregates into the shared engine capability.
-- Existing version graph rows and version IDs remain unchanged.

CREATE TABLE IF NOT EXISTS `assetversionstats` (
  `appKey` varchar(32) NOT NULL,
  `assetType` int(11) NOT NULL,
  `assetID` int(11) NOT NULL,
  `versionID` bigint(20) UNSIGNED NOT NULL,
  `gamesPlayed` int(11) NOT NULL DEFAULT 0,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`appKey`,`assetType`,`assetID`,`versionID`),
  KEY `idx_assetversionstats_version` (`versionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `assetversionstats`
  (`appKey`, `assetType`, `assetID`, `versionID`, `gamesPlayed`, `wins`, `losses`)
SELECT
  'AzukiDeck', 1, `deckID`, `versionID`, `gamesPlayed`, `wins`, `losses`
FROM `azukideckversionstats`
ON DUPLICATE KEY UPDATE
  `gamesPlayed` = GREATEST(`assetversionstats`.`gamesPlayed`, VALUES(`gamesPlayed`)),
  `wins` = GREATEST(`assetversionstats`.`wins`, VALUES(`wins`)),
  `losses` = GREATEST(`assetversionstats`.`losses`, VALUES(`losses`));
