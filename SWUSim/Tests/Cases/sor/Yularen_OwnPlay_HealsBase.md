# SOR_109 Colonel Yularen (2/3) — "When you play a [Command] unit (including this one): Heal 1 damage
# from your base." Yularen is itself a Command unit, so playing HIM (the "including this one" clause)
# heals 1 from P1's base (3 → 2).

## GIVEN
P1LeaderBase: SOR_009/SOR_024:3
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_109
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:1
