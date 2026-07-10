# SHD_208 Final Showdown (Event, cost 6, Cunning/Cunning) — "...At the start of the regroup phase, you
# lose the game." P1 plays it, then passes to the regroup phase, where the lose-check fires: P1 (the
# caster) loses, so P2 wins the game.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: SHD_208

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P2WIN
