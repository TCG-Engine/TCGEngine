# TWI_227 Prisoner of War — the only enemy unit in the captor's arena is a Vehicle (SOR_225 TIE),
# which the "non-Vehicle" filter excludes. No valid target → the event fizzles cleanly (nothing
# captured, no droids). Captor JTL_069 and target TIE are both in the space arena.

## GIVEN
CommonSetup: yyk/grw/{myResources:4;handCardIds:TWI_227}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION
