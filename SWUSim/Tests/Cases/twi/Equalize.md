# TwoDebuffsWhenFewerUnits
#// TWI_055 Equalize (Event, cost 3, Vigilance) — "Give a unit -2/-2 for this phase. Then, if you control
#// fewer units than that unit's controller, give another unit -2/-2 for this phase." P1 controls 1 unit
#// vs P2's 2 → both P2 units chosen get -2/-2 (3 power → 1 each).

## GIVEN
CommonSetup: bbw/grw/{myResources:3;handCardIds:TWI_055}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:1:POWER:1
