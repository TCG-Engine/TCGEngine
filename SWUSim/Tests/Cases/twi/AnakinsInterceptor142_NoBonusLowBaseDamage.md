# TWI_142 Anakin's Interceptor — with only 14 damage on P1's base (< 15), no +2/+0: power stays 2.

## GIVEN
CommonSetup: rrw/grw/{myResources:0;myBaseDamage:14}
P1OnlyActions: true
WithP1SpaceArena: TWI_142:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
