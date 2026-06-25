# SOR_109 Colonel Yularen — playing a NON-[Command] unit (SOR_237, Heroism only) does NOT trigger the
# heal; P1's base stays at 3 damage.

## GIVEN
CommonSetup: ggw/brw/{
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_109:1:0
WithP1Hand: SOR_237
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P1SPACEARENACOUNT:1
