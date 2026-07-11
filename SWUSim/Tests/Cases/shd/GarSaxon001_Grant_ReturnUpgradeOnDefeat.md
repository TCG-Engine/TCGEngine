# SHD_001 Gar Saxon (deployed grant) — "Each friendly upgraded unit gains: When Defeated: You may return
# an upgrade that was attached to this unit to its owner's hand." P1's SOR_128 wears SOR_069; on P2's
# turn, P2's SOR_015 (4/7) attacks and defeats it. P1 (controlling Gar Saxon) gets SOR_069 back to hand.
# (Resolved as a benefit-only auto-return so it works on the ENEMY's turn — a controller-side When-Defeated
# trigger wouldn't drain there.)

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_001}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_015:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_069
