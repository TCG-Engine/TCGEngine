# UseForce_PlayDiscounted
#// LOF_094 Jedi Consular — Action [Exhaust, use the Force]: play a unit from hand at −2. With the Force and
#// SOR_095 (cost 3 → 1) in hand, P1 exhausts the Consular, uses the Force, and plays SOR_095 for 1.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:SOR_095}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_094:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:EXHAUSTED
