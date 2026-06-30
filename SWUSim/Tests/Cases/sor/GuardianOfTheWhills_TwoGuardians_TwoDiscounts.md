# SOR_061 Guardian of the Whills — each Guardian has its OWN per-round charge, so two Guardians grant
# two separate discounts. Two SOR_069 (cost 1), each attached to a different Guardian, both cost 0:
# 4 ready resources → 4 left. (One discount only → 3 left; no discount → 2 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:4
