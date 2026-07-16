# WhenPlayedDraw
#// SOR_111 Patrolling V-Wing (Space, 1/1) — When Played: draw a card. Playing it
#// (hand goes 1 → 0) then draws 1 → hand 1; it enters the space arena.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
