# PlayedUnitDealsPower
#// ASH_102 Ravager (Space, 8/10, Restore 2) — When you play a unit: you may have it deal damage equal to
#// its power to a unit in the same arena. With Ravager in play, P1 plays SOR_095 (3 power); it deals 3 to
#// the enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: yyw/yyk/{myResources:6;handCardIds:SOR_095}
WithP1SpaceArena: ASH_102:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENACOUNT:0
