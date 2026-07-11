# SHD_081 General Tagge (2-cost, Imperial/Official) — "When Played: Give an Experience token to each of up
# to 3 Trooper units." The two Trooper units (SOR_095, SEC_080) each get an Experience token; the
# non-Trooper LAW_124 is not eligible.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_081
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:CARDID:LAW_124
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
