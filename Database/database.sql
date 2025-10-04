-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 03, 2022 at 02:11 AM
-- Server version: 10.4.22-MariaDB
-- PHP Version: 8.1.2
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE TABLE `completedgame` (
  `CompletionTime` timestamp NOT NULL DEFAULT current_timestamp(),
  `WinningHero` char(15) NOT NULL,
  `LosingHero` char(15) NOT NULL,
  `WinningPID` int(11) DEFAULT NULL,
  `LosingPID` int(11) DEFAULT NULL,
  `WinningPlayer` tinyint(4) DEFAULT NULL,
  `WinnerHealth` int(11) DEFAULT NULL,
  `FirstPlayer` tinyint(4) DEFAULT NULL,
  `NumTurns` int(11) NOT NULL,
  `Format` int(11) DEFAULT NULL,
  `GameID` int(22) NOT NULL,
  `WinnerDeck` varchar(1000) DEFAULT NULL,
  `LoserDeck` varchar(1000) DEFAULT NULL,
  `lastAuthKey` varchar(128) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `favoritedeck`
--

CREATE TABLE `favoritedeck` (
  `decklink` varchar(128) NOT NULL,
  `usersId` int(11) NOT NULL,
  `name` varchar(128) NOT NULL,
  `hero` varchar(15) NOT NULL,
  `format` varchar(32) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `pwdreset`
--

CREATE TABLE `pwdreset` (
  `pwdResetId` int(11) NOT NULL,
  `pwdResetEmail` text NOT NULL,
  `pwdResetSelector` text NOT NULL,
  `pwdResetToken` longtext NOT NULL,
  `pwdResetExpires` text NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
-- --------------------------------------------------------
--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `usersId` int(11) NOT NULL,
  `usersUid` varchar(128) NOT NULL,
  `usersEmail` varchar(128) NOT NULL,
  `usersPwd` varchar(128) NOT NULL,
  `simLink` varchar(64) DEFAULT NULL,
  `rememberMeToken` varchar(64) DEFAULT NULL,
  `patreonAccessToken` varchar(64) DEFAULT NULL,
  `patreonRefreshToken` varchar(64) DEFAULT NULL,
  `discordID` varchar(32) DEFAULT NULL,
  `teamID` int(11) DEFAULT NULL,
  `patreonEnum` varchar(64) DEFAULT NULL,
  `isBanned` tinyint(1) NOT NULL DEFAULT 0,
  `lastLoggedIP` varchar(32) DEFAULT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;

-- --------------------------------------------------------
--
-- Table structure for table `ownership`
--
CREATE TABLE `ownership` (
  `assetType` int(11) NOT NULL,
  `assetIdentifier` int(11) NOT NULL,
  `assetOwner` int(11) NOT NULL,
  `assetStatus` int(11) DEFAULT NULL,
  `assetName` varchar(128) DEFAULT NULL,
  `assetVisibility` int(11) DEFAULT NULL,
  `assetFolder` int(11) NOT NULL DEFAULT 0,
  `assetSource` int(11) DEFAULT NULL,
  `assetSourceID` varchar(32) DEFAULT NULL,
  `numLikes` int(11) NOT NULL DEFAULT 0,
  `keyIndicator1` varchar(16) DEFAULT NULL,
  `keyIndicator2` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `opponentdeckstats` (
  `deckID` int(11) NOT NULL,
  `leaderID` varchar(16) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 0,
  `source` int(11) NOT NULL DEFAULT 0,
  `winsVsGreen` int(11) NOT NULL DEFAULT 0,
  `totalVsGreen` int(11) NOT NULL DEFAULT 0,
  `winsVsBlue` int(11) NOT NULL DEFAULT 0,
  `totalVsBlue` int(11) NOT NULL DEFAULT 0,
  `winsVsRed` int(11) NOT NULL DEFAULT 0,
  `totalVsRed` int(11) NOT NULL DEFAULT 0,
  `winsVsYellow` int(11) NOT NULL DEFAULT 0,
  `totalVsYellow` int(11) NOT NULL DEFAULT 0,
  `winsVsColorless` int(11) NOT NULL DEFAULT 0,
  `totalVsColorless` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `opponentdeckstats`
--
ALTER TABLE `opponentdeckstats`
  ADD PRIMARY KEY (`deckID`,`leaderID`,`source`,`version`) USING BTREE;

CREATE TABLE `assetversions` (
  `assetType` int(11) NOT NULL,
  `assetID` int(11) NOT NULL,
  `assetHash` varchar(64) NOT NULL,
  `versionNumber` int(11) NOT NULL,
  `versionName` int(11) NOT NULL,
  `lastUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assetJSON` longtext NOT NULL,
  `nearestPriorVersion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `assetversions`
--
ALTER TABLE `assetversions`
  ADD PRIMARY KEY (`assetType`,`assetID`,`assetHash`) USING BTREE;


CREATE TABLE `deck_game_raw_data` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `deckID` bigint(20) UNSIGNED NOT NULL,
  `gameStats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`gameStats`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `source` int(11) NOT NULL DEFAULT 0,
  `gameName` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `deck_game_raw_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deckID` (`deckID`,`gameName`) USING BTREE;

--
-- Indexes for table `completedgame`
--
ALTER TABLE `completedgame`
ADD PRIMARY KEY (`GameID`),
  ADD KEY `FK_WINNING_PLAYER` (`WinningPID`),
  ADD KEY `FK_LOSING_PLAYER` (`LosingPID`);
--
-- Indexes for table `favoritedeck`
--
ALTER TABLE `favoritedeck`
  ADD PRIMARY KEY (`decklink`,`usersId`) USING BTREE,
  ADD KEY `usersId` (`usersId`);
--
-- Indexes for table `pwdreset`
--
ALTER TABLE `pwdreset`
ADD PRIMARY KEY (`pwdResetId`);
--
-- Indexes for table `users`
--
ALTER TABLE `users`
ADD PRIMARY KEY (`usersId`),
  ADD KEY `usersUid` (`usersUid`),
  ADD KEY `discordID` (`discordID`);
--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `completedgame`
--
ALTER TABLE `completedgame`
MODIFY `GameID` int(22) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 96;
--
-- AUTO_INCREMENT for table `pwdreset`
--
ALTER TABLE `pwdreset`
MODIFY `pwdResetId` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 2;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
MODIFY `usersId` int(11) NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 5;
--
-- Constraints for dumped tables
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Indexes for dumped tables
--

--
-- AUTO_INCREMENT for dumped tables
--


CREATE TABLE `savedsettings` (
  `playerId` int(11) NOT NULL,
  `settingNumber` int(11) NOT NULL,
  `settingValue` int(11) NOT NULL
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4;
ALTER TABLE `savedsettings`
ADD PRIMARY KEY (`playerId`, `settingNumber`);


--
-- Table structure for table `blocklist`
--

CREATE TABLE `blocklist` (
  `blockingPlayer` int(11) NOT NULL,
  `blockedPlayer` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `deckstats` (
  `deckID` int(11) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 0,
  `source` int(11) NOT NULL DEFAULT 0,
  `numWins` int(11) NOT NULL DEFAULT 0,
  `numPlays` int(11) NOT NULL DEFAULT 0,
  `playsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `turnsInWins` int(11) NOT NULL DEFAULT 0,
  `totalTurns` int(11) NOT NULL DEFAULT 0,
  `cardsResourcedInWins` int(11) NOT NULL DEFAULT 0,
  `totalCardsResourced` int(11) NOT NULL DEFAULT 0,
  `remainingHealthInWins` int(11) NOT NULL DEFAULT 0,
  `winsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `winsGoingSecond` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `deckstats`
--
ALTER TABLE `deckstats`
  ADD PRIMARY KEY (`deckID`,`source`,`version`) USING BTREE;

CREATE TABLE `carddeckstats` (
  `deckID` int(11) NOT NULL,
  `cardID` varchar(16) NOT NULL,
  `version` int(11) NOT NULL DEFAULT 0,
  `source` int(11) NOT NULL DEFAULT 0,
  `timesIncluded` int(11) NOT NULL DEFAULT 0,
  `timesIncludedInWins` int(11) NOT NULL DEFAULT 0,
  `timesPlayed` int(11) NOT NULL DEFAULT 0,
  `timesPlayedInWins` int(11) NOT NULL DEFAULT 0,
  `timesResourced` int(11) NOT NULL DEFAULT 0,
  `timesResourcedInWins` int(11) NOT NULL DEFAULT 0,
  `timesDiscarded` int(11) NOT NULL DEFAULT 0,
  `timesDiscardedInWins` int(11) NOT NULL DEFAULT 0,
  `timesDrawn` int(11) NOT NULL DEFAULT 0,
  `timesDrawnInWins` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `carddeckstats`
--
ALTER TABLE `carddeckstats`
  ADD PRIMARY KEY (`deckID`,`cardID`,`source`,`version`) USING BTREE;

--
-- Indexes for table `blocklist`
--
ALTER TABLE `blocklist`
  ADD PRIMARY KEY (`blockingPlayer`,`blockedPlayer`),
  ADD KEY `blockingPlayer` (`blockingPlayer`);

--
-- Indexes for table `ownership`
--
ALTER TABLE `ownership`
  ADD PRIMARY KEY (`assetType`,`assetIdentifier`),
  ADD KEY `idx_owner_type` (`assetOwner`,`assetType`),
  ADD KEY `assetVisibility` (`assetVisibility`,`keyIndicator1`,`keyIndicator2`) USING BTREE;

CREATE TABLE `cardmetastats` (
  `cardID` varchar(16) NOT NULL,
  `week` int(11) NOT NULL,
  `timesIncluded` int(11) NOT NULL DEFAULT 0,
  `timesIncludedInWins` int(11) NOT NULL DEFAULT 0,
  `timesPlayed` int(11) NOT NULL DEFAULT 0,
  `timesPlayedInWins` int(11) NOT NULL DEFAULT 0,
  `timesResourced` int(11) NOT NULL DEFAULT 0,
  `timesResourcedInWins` int(11) NOT NULL DEFAULT 0,
  `timesDiscarded` int(11) NOT NULL DEFAULT 0,
  `timesDiscardedInWins` int(11) NOT NULL DEFAULT 0,
  `timesAttacking` int(11) NOT NULL DEFAULT 0,
  `timesAttackingInWins` int(11) NOT NULL DEFAULT 0,
  `timesAttacked` int(11) NOT NULL DEFAULT 0,
  `timesAttackedInWins` int(11) NOT NULL DEFAULT 0,
  `timesActivated` int(11) NOT NULL DEFAULT 0,
  `timesActivatedInWins` int(11) NOT NULL DEFAULT 0,
  `searchWhiffs` int(11) NOT NULL DEFAULT 0,
  `searchWhiffsInWins` int(11) NOT NULL DEFAULT 0,
  `timesDrawn` int(11) NOT NULL DEFAULT 0,
  `timesDrawnInWins` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `cardmetastats`
  ADD PRIMARY KEY (`cardID`,`week`),
  ADD KEY `week` (`week`);


CREATE TABLE `deckmetastats` (
  `leaderID` varchar(16) NOT NULL,
  `baseID` varchar(16) NOT NULL,
  `week` int(11) NOT NULL DEFAULT 0,
  `numWins` int(11) NOT NULL DEFAULT 0,
  `numPlays` int(11) NOT NULL DEFAULT 0,
  `playsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `turnsInWins` int(11) NOT NULL DEFAULT 0,
  `totalTurns` int(11) NOT NULL DEFAULT 0,
  `cardsResourcedInWins` int(11) NOT NULL DEFAULT 0,
  `totalCardsResourced` int(11) NOT NULL DEFAULT 0,
  `remainingHealthInWins` int(11) NOT NULL DEFAULT 0,
  `winsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `winsGoingSecond` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `deckmetastats`
  ADD PRIMARY KEY (`leaderID`,`baseID`,`week`),
  ADD KEY `week` (`week`);


CREATE TABLE `deckmetamatchupstats` (
  `leaderID` varchar(16) NOT NULL,
  `baseID` varchar(16) NOT NULL,
  `opponentLeaderID` varchar(16) NOT NULL,
  `opponentBaseID` varchar(16) NOT NULL,
  `week` int(11) NOT NULL DEFAULT 0,
  `numWins` int(11) NOT NULL DEFAULT 0,
  `numPlays` int(11) NOT NULL DEFAULT 0,
  `playsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `turnsInWins` int(11) NOT NULL DEFAULT 0,
  `totalTurns` int(11) NOT NULL DEFAULT 0,
  `cardsResourcedInWins` int(11) NOT NULL DEFAULT 0,
  `totalCardsResourced` int(11) NOT NULL DEFAULT 0,
  `remainingHealthInWins` int(11) NOT NULL DEFAULT 0,
  `winsGoingFirst` int(11) NOT NULL DEFAULT 0,
  `winsGoingSecond` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `deckmetamatchupstats`
  ADD PRIMARY KEY (`leaderID`,`baseID`,`opponentLeaderID`,`opponentBaseID`,`week`),
  ADD KEY `week` (`week`);


CREATE TABLE `team` (
  `teamID` int(11) NOT NULL,
  `TeamName` varchar(64) NOT NULL,
  `ownerID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `team`
  ADD PRIMARY KEY (`teamID`);

ALTER TABLE `team`
  MODIFY `teamID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `teaminvite` (
  `inviteID` int(11) NOT NULL,
  `teamID` int(11) NOT NULL,
  `userID` int(11) NOT NULL,
  `invitedBy` int(11) NOT NULL,
  `inviteTime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `teaminvite`
  ADD PRIMARY KEY (`inviteID`),
  ADD KEY `userID` (`userID`),
  ADD KEY `teamID` (`teamID`);

ALTER TABLE `teaminvite`
  MODIFY `inviteID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `meleetournament` (
  `tournamentID` int(11) NOT NULL,
  `tournamentLink` int(11) NOT NULL,
  `tournamentName` varchar(256) NOT NULL,
  `tournamentDate` date NOT NULL,
  `roundId` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `meleetournament`
  ADD PRIMARY KEY (`tournamentID`);

ALTER TABLE `meleetournament`
  MODIFY `tournamentID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `meleetournamentdeck` (
  `deckID` int(11) NOT NULL,
  `tournamentID` int(11) NOT NULL,
  `rank` int(11) DEFAULT NULL,
  `player` varchar(64) DEFAULT NULL,
  `leader` varchar(16) DEFAULT NULL,
  `base` varchar(16) DEFAULT NULL,
  `matchWins` int(11) NOT NULL DEFAULT 0,
  `matchLosses` int(11) NOT NULL DEFAULT 0,
  `matchDraws` int(11) NOT NULL DEFAULT 0,
  `gameWins` int(11) NOT NULL DEFAULT 0,
  `gameLosses` int(11) NOT NULL DEFAULT 0,
  `gameDraws` int(11) NOT NULL DEFAULT 0,
  `points` int(11) NOT NULL DEFAULT 0,
  `OMWP` float DEFAULT NULL,
  `TGWP` float DEFAULT NULL,
  `OGWP` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `meleetournamentdeck`
  ADD PRIMARY KEY (`deckID`);

ALTER TABLE `meleetournamentdeck`
  MODIFY `deckID` int(11) NOT NULL AUTO_INCREMENT;

CREATE TABLE `meleetournamentmatchup` (
  `matchupID` int(11) NOT NULL,
  `player` int(11) DEFAULT NULL,
  `opponent` int(11) DEFAULT NULL,
  `wins` int(11) NOT NULL DEFAULT 0,
  `losses` int(11) NOT NULL DEFAULT 0,
  `draws` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `meleetournamentmatchup`
  ADD PRIMARY KEY (`matchupID`);

ALTER TABLE `meleetournamentmatchup`
  MODIFY `matchupID` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------
--
-- Tables for OAuth 2.0 provider functionality
--

CREATE TABLE `oauth_clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(80) NOT NULL,
  `client_name` varchar(128) NOT NULL,
  `redirect_uri` text NOT NULL,
  `grant_types` varchar(80) DEFAULT NULL,
  `scope` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `oauth_access_tokens` (
  `access_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp(),
  `scope` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `oauth_authorization_codes` (
  `authorization_code` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `redirect_uri` text DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp(),
  `scope` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `oauth_refresh_tokens` (
  `refresh_token` varchar(40) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `expires` timestamp NOT NULL DEFAULT current_timestamp(),
  `scope` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `oauth_scopes` (
  `scope` varchar(80) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Indexes for OAuth tables
ALTER TABLE `oauth_clients`
  ADD PRIMARY KEY (`client_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `oauth_access_tokens`
  ADD PRIMARY KEY (`access_token`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `oauth_authorization_codes`
  ADD PRIMARY KEY (`authorization_code`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `oauth_refresh_tokens`
  ADD PRIMARY KEY (`refresh_token`),
  ADD KEY `client_id` (`client_id`),
  ADD KEY `user_id` (`user_id`);

ALTER TABLE `oauth_scopes`
  ADD PRIMARY KEY (`scope`);

-- Insert default scopes
INSERT INTO `oauth_scopes` (`scope`, `is_default`, `description`) VALUES
('profile', 1, 'Access to user profile information'),
('email', 1, 'Access to user email'),
('decks', 0, 'Access to user deck information'),
('stats', 0, 'Access to user gameplay statistics');

-- --------------------------------------------------------