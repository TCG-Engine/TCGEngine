# SOR_192 Ezra Bridger — On Attack End with an empty deck: there is no top card to look at, so the
# ability fizzles with no decision (no option popup). Ezra still attacks P2's base for 3, and the
# turn proceeds with no pending decision.

## GIVEN
CommonSetup: rrw/rrw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_192:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1DECKCOUNT:0
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:1
