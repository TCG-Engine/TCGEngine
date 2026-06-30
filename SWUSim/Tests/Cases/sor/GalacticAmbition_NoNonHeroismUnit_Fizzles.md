# SOR_235 Galactic Ambition — guard: the only other card in hand is a [Heroism] unit (SOR_095), which
# is NOT a legal target → the event fizzles: no free play, no self-base damage, SOR_095 stays in hand.

## GIVEN
CommonSetup: rrk/rrk/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_235
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0
P1HANDCOUNT:1
