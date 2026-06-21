# LAW_159 Expendable Mercenary (3/3) — When Defeated: you may resource this unit from its owner's
# discard pile. Attacks SOR_046 (3/7) and dies; it returns as a resource (exhausted). P1 started with 0
# resources -> 1 (exhausted).

## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: LAW_159:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
