# Control for JTL_105: with NO Starhawk in play, 2 ready resources cannot pay SOR_046's printed cost 4,
# so the play is rejected and SOR_046 stays in hand. (Proves the Starhawk's halving is what enables the
# cheap play in Starhawk105_HalvesCost_PlaysExpensiveCheap.)

## GIVEN
CommonSetup: bbw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:2
