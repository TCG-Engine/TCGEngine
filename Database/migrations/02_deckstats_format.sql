-- Per-format deck stats: add `format` to the four per-deck stats tables and fold it into each PK.
-- SWUStats stats DB only (local docker DB: `swudeck`). Not the shared cross-app `ownership` table.
--
-- DEFAULT 'premier' backfills every existing row to premier (chosen backfill). The DROP/ADD PRIMARY
-- KEY is an ALGORITHM=COPY table rewrite on large tables (prod-data copy) -- apply in a low-traffic
-- window. Expand-first: old code omits `format` on insert (-> premier) and, pre-push, only premier
-- rows exist, so its format-less WHERE/UPDATE stays correct until the code push adds `format`.

ALTER TABLE `deckstats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `deckstats` DROP PRIMARY KEY, ADD PRIMARY KEY (`deckID`,`source`,`version`,`format`);

ALTER TABLE `carddeckstats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `carddeckstats` DROP PRIMARY KEY, ADD PRIMARY KEY (`deckID`,`cardID`,`source`,`version`,`format`);

ALTER TABLE `opponentdeckstats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `opponentdeckstats` DROP PRIMARY KEY, ADD PRIMARY KEY (`deckID`,`leaderID`,`source`,`version`,`format`);

ALTER TABLE `opponentnamedbasestats` ADD COLUMN `format` varchar(16) NOT NULL DEFAULT 'premier';
ALTER TABLE `opponentnamedbasestats` DROP PRIMARY KEY, ADD PRIMARY KEY (`deckID`,`leaderID`,`baseID`,`source`,`format`);
