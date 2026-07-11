# SHD_001 Gar Saxon — the grant is gated on controlling Gar Saxon. Without him (default leader), a
# defeated upgraded unit's upgrade simply goes to the discard: SOR_128 + SOR_069 both end in P1's discard,
# nothing returns to hand.

## GIVEN
CommonSetup: rrk/rrk
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_015:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2
