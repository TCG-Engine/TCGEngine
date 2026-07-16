# DealsDamageEqualResources
#// SOR_090 Devastator (cost 10) — When Played: you may deal damage to a unit equal to
#// the number of resources you control. P1 controls 10 resources, so the chosen enemy
#// (Consular Security Force, 7 HP) takes 10 and is defeated.

## GIVEN
CommonSetup: ggk/ggk/{myResources:10;handCardIds:SOR_090}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
