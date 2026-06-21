# LOF_217 Force Slow — Give an exhausted unit -8/-0 for this phase. The exhausted enemy SOR_046 (power 3)
# drops to power 0.

## GIVEN
CommonSetup: yyw/ggk/{myResources:1;handCardIds:LOF_217}
P1OnlyActions: true
WithP2GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
