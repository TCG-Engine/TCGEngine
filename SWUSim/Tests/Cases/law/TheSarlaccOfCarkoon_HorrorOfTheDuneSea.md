# OnAttackDiscardUnitDealPower
#// LAW_163 The Sarlacc of Carkoon (8/9) — On Attack: put a unit from your discard on the bottom of your
#// deck; deal damage equal to that unit's power to an enemy ground unit. SOR_046 (power 3) from discard
#// -> deal 3 to the enemy SOR_046 in play.

## GIVEN
CommonSetup: grk/bgw/{discardCardIds:SOR_046}
P1OnlyActions: true
WithP1GroundArena: LAW_163:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myDiscard-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DECKCOUNT:1
P1DISCARDCOUNT:0
