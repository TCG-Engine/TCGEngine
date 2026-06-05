# JTL_100 Poe Dameron — "When played as a unit": creates X-Wing token then player
# accepts the optional free-attach onto a friendly Vehicle with 0 pilots.
#
# JTL_100: cost 4, Command+Heroism, Ground, power 3/hp 3, upgradePower +2, upgradeHp +3.
# Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
#
# canUnit = true (4 >= 4), canPilot = true (4 >= 2, SOR_237 empty) → Unit/Pilot prompt.
# Player picks "Unit": JTL_100 enters ground arena at idx 0.
# WhenPlayed fires (single trigger, no two-trigger ordering):
#   1. X-Wing token (JTL_T02, Space) created → space arena: SOR_237 (idx 0), JTL_T02 (idx 1).
#   2. Free-attach MZMAYCHOOSE: SOR_237 is a Vehicle with 0 pilots → 1 target.
#      Player accepts: AnswerDecision:mySpaceArena-0.
#   3. JTL_100 is removed from ground arena and attached as Pilot subcard on SOR_237.
#
# Final state:
#   Ground arena count: 0 (JTL_100 left the arena to become an upgrade).
#   Space arena count: 2 (SOR_237 at idx 0, JTL_T02 at idx 1).
#   SOR_237 upgradeCount: 1 (JTL_100 as pilot, IsPilot=true).
#   SOR_237 power: 2 (base) + 2 (JTL_100 upgradePower) = 4.
#   SOR_237 hp:    3 (base) + 3 (JTL_100 upgradeHp)    = 6.

## GIVEN
P1LeaderBase: SOR_009/SOR_024
P2LeaderBase: SOR_009/SOR_024
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 4
WithP1Hand: JTL_100
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_100
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:6
P1SPACEARENAUNIT:1:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0
