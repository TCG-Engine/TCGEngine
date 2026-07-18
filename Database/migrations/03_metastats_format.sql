-- Per-format meta: add `format` to the three meta tables and fold it into each PK.
-- SWUStats stats DB only (local docker `swudeck`). DEFAULT 'premier' backfills all rows (all
-- existing meta is premier). DROP/ADD PRIMARY KEY is a table-copy rewrite -- low-traffic window.
-- Expand-first: old code omits `format` (-> premier) and every existing reader defaults to premier.

ALTER TABLE `deckmetastats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `deckmetastats` DROP PRIMARY KEY, ADD PRIMARY KEY (`leaderID`,`baseID`,`week`,`format`);

ALTER TABLE `cardmetastats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `cardmetastats` DROP PRIMARY KEY, ADD PRIMARY KEY (`cardID`,`week`,`format`);

ALTER TABLE `deckmetamatchupstats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `deckmetamatchupstats` DROP PRIMARY KEY, ADD PRIMARY KEY (`leaderID`,`baseID`,`opponentLeaderID`,`opponentBaseID`,`week`,`format`);
