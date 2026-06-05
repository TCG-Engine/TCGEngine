# SOR_102 Home One — "When Played: Play a [Heroism] unit from your discard pile. It costs 3 less."
# Affordability guard: the discount play must still be PAID for. With exactly 8 resources, all are
# spent deploying Home One (cost 8), leaving 0 ready. SOR_095 (cost 2 -> 0 after -3) is affordable;
# SOR_046 (cost 4 -> 1 after -3) is NOT. Only the affordable unit may be offered, so the single
# remaining target auto-resolves and plays — no chance to pick the unplayable one and fizzle.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
