# JTL_038 Corvus — When Played: may attach a friendly Pilot unit/upgrade to this. (Defeat all upgrades on
# that Pilot and remove all damage from it.) P1 has Paige (JTL_046) as a UNIT with 2 damage and a normal
# upgrade (SOR_120). Corvus enters and attaches Paige → her SOR_120 upgrade is defeated (to discard) and
# her damage cleared; Paige becomes Corvus's only pilot subcard; the ground arena empties.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_038
WithP1GroundArena: JTL_046:1:2
WithP1GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
