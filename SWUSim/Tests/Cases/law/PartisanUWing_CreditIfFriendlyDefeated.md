# LAW_161 Partisan U-Wing (Command, cost 5, space) — When Played: if a friendly unit was defeated this
# phase, create a Credit token. SOR_128 (3/1) attacks into SOR_046 and dies, then the U-Wing creates a Credit.

## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_161

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:1
