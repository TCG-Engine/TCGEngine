# SEC_243 FN Trooper Corps (Ground, 4/5, Villainy, cost 5) — When Played: give an Experience token to
#   another friendly unit. (Plot auto.)

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_243

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
