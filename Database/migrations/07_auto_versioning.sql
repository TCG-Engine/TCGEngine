-- Auto-versioning is intentionally a selective migration. The legacy
-- assetversions draft and manual versions embedded in gamestate files remain
-- untouched and are not imported.

CREATE TABLE IF NOT EXISTS `assetautoversions` (
  `versionID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `appKey` varchar(32) NOT NULL,
  `assetType` int(11) NOT NULL,
  `assetID` int(11) NOT NULL,
  `assetHash` varchar(64) NOT NULL,
  `versionNumber` int(11) NOT NULL,
  `versionName` varchar(255) NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assetJSON` longtext NOT NULL,
  `parentVersionID` bigint(20) UNSIGNED DEFAULT NULL,
  `depth` int(11) NOT NULL DEFAULT 0,
  `distanceFromParent` int(11) NOT NULL DEFAULT 0,
  `deltaJSON` longtext NOT NULL,
  PRIMARY KEY (`versionID`),
  UNIQUE KEY `uq_assetautoversions_hash` (`appKey`,`assetType`,`assetID`,`assetHash`),
  UNIQUE KEY `uq_assetautoversions_number` (`appKey`,`assetType`,`assetID`,`versionNumber`),
  KEY `idx_assetautoversions_parent` (`parentVersionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `azukideckversionstats` (
  `deckID` int(11) NOT NULL,
  `versionID` bigint(20) UNSIGNED NOT NULL,
  `gamesPlayed` int(11) NOT NULL DEFAULT 0,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`deckID`,`versionID`),
  KEY `idx_azukideckversionstats_version` (`versionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
