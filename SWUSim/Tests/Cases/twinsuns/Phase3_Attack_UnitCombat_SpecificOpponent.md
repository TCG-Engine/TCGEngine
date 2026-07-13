# Twin Suns Phase 3: unit-vs-unit combat against a SPECIFIC opponent's unit (a p{n}GroundArena defender).
# P1's 3/3 attacks P3's 3/3 → both take 3 and are defeated simultaneously, and each goes to its OWN
# owner's discard. This exercises the full p{n} defender path: the union offers P3's unit, the picker
# resolves p3GroundArena-0, GetZoneObject fetches that defender (a Phase-1 gap this fixes), combat damage
# applies, and defeat routes to the correct owner (P3's card → P3's discard, P1's → P1's).

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3GroundArena: SOR_229:1:0
WithP3Base: SOR_019

## WHEN
- P1>AttackGroundArena:0:P3G0

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P3DISCARDCOUNT:1
