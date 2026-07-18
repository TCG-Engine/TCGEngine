CREATE TABLE IF NOT EXISTS `azukicarddeckstats` (
  `deckID` int(11) NOT NULL,
  `cardID` varchar(128) NOT NULL,
  `gamesIncluded` int(11) NOT NULL DEFAULT 0,
  `gamesIncludedInWins` int(11) NOT NULL DEFAULT 0,
  `copiesIncluded` int(11) NOT NULL DEFAULT 0,
  `copiesIncludedInWins` int(11) NOT NULL DEFAULT 0,
  `timesPlayed` int(11) NOT NULL DEFAULT 0,
  `timesPlayedInWins` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`deckID`,`cardID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
