# LAW_078 Sabine Wren (3/3, Ambush) — When Played: you may defeat a non-unique upgrade (any upgrade if
# you control a Vigilance or Command unit). No enemy units (so Ambush adds no trigger); P1 controls no
# Vigilance/Command unit, so only non-unique upgrades are offered: defeat SOR_120 on SOR_128.

## GIVEN
CommonSetup: ryw/bgw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Hand: LAW_078

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
