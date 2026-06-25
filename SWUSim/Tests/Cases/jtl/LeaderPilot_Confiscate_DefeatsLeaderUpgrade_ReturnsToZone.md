# JTL_001 Asajj deployed as pilot on SOR_225 (TIE/ln Fighter, Space).
# P2 plays SOR_251 Confiscate ("Defeat an upgrade.") targeting the JTL_001 pilot.
# SOR_225 base 2/1; JTL_001 upgradePower=3, upgradeHp=4 → host 5/5.
# After Confiscate: host SURVIVES (space arena count=1), JTL_001 returns to leader zone
# (NOT in discard, P1LEADER:NOTDEPLOYED), P2 spends 1 resource on Confiscate.

## GIVEN
CommonSetup: gbk/brw/{
  myLeader:JTL_001;
  myBase:SOR_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1Resources: 6
WithP2Resources: 1
WithP2Hand: SOR_251
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P2>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1LEADER:NOTDEPLOYED
P1DISCARDCOUNT:0
P2RESAVAILABLE:0
