# SEC_035 Darth Sion — boundary guard: 6 power at defeat (< 7) does NOT return him to hand.
# One Experience token makes Sion 6/6. He attacks the 8/8 SOR_039 and dies to the counter. His
# power-at-defeat is 6 (< 7), so the When Defeated does nothing — he stays in P1's discard pile.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_035:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_035
P2GROUNDARENACOUNT:1
