# SOR_041 Power of the Dark Side — when the opponent controls no units the event fizzles cleanly: it
# resolves to P1's discard and nothing is defeated (no dangling decision).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_041
WithP1Resources: 3

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
