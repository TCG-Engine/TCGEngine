# LOF_258 Peli Motto — On Attack: give an Experience token to a friendly Vehicle or Droid unit. Peli
# attacks the base and gives an Experience token to the friendly Alliance X-Wing (Vehicle).

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_258:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
