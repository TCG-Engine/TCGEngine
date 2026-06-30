# SOR_109 Colonel Yularen — with Yularen already in play, playing ANOTHER [Command] unit (SOR_095, a
# Command,Heroism unit) heals 1 from P1's base (3 → 2).

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:2
P1GROUNDARENACOUNT:2
