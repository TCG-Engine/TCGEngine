ALTER TABLE `azukicarddeckstats`
  ADD COLUMN `timesDrawn` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `timesDrawnInWins` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `timesAttacks` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `timesAttacksInWins` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `timesTargetedByAttacks` int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `timesTargetedByAttacksInWins` int(11) NOT NULL DEFAULT 0;
