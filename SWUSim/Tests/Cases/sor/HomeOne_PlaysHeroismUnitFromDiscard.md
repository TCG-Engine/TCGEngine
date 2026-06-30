# SOR_102 Home One (Command/Heroism unit, cost 8, 7/7, Rebel/Capital Ship) — "Restore 2. Each other
# friendly unit gains Restore 1. When Played: Play a [Heroism] unit from your discard pile. It costs 3
# less." (Restore/Restore-grant already implemented.) Two Heroism units seeded in discard; choosing
# SOR_095 (cost 3 → free after -3) plays it into the ground arena, leaving SOR_046 in discard.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;discardCardIds:SOR_095,SOR_046}
P1OnlyActions: true
WithP1Hand: SOR_102

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
