# JTL_100 Poe Dameron — "When played as a unit": creates X-Wing token, player DECLINES
# the optional free-attach. JTL_100 stays as a unit; token still created.
#
# JTL_100: cost 4, Command+Heroism, Ground, power 3/hp 3.
# Leader SOR_009 Leia (Command+Heroism) + Base SOR_024 (Command) → 0 aspect penalty.
#
# canUnit = true (4 >= 4), canPilot = true (4 >= 2, SOR_237 empty) → Unit/Pilot prompt.
# Player picks "Unit": JTL_100 enters ground arena at idx 0.
# WhenPlayed fires:
#   1. X-Wing token (JTL_T02) created → space: SOR_237 (idx 0), JTL_T02 (idx 1).
#   2. Free-attach MZMAYCHOOSE with SOR_237 as target.
#      Player declines: AnswerDecision:-
#
# Final state:
#   Ground arena count: 1 (JTL_100 stays as unit at idx 0).
#   Space arena count: 2 (SOR_237 at idx 0, JTL_T02 at idx 1).
#   SOR_237 has no upgrade (free-attach declined).

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
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_100
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:JTL_T02
P1HANDCOUNT:0
P1RESAVAILABLE:0
