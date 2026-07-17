# TwoLeaderDeckImport
#// Locks in that LoadPlayerDeck pushing multiple Leader zone entries produces the right
#// P1LEADERCOUNT/P1LEADER{n} shape -- the same zone-push code path the new deck-link
#// two-leader import (SWUNormalizeStandardJSON reading `secondleader`) will drive.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:SOR_001;
  myLeader2:SOR_002
}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>Pass

## EXPECT
P1LEADERCOUNT:2
P1LEADER0:READY
P1LEADER1:READY
