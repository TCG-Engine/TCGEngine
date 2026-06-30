# SOR_156 Benthic "Two Tubes" — the granted Raid 2 is "for this phase". After Benthic attacks (granting
# SOR_164 Raid 2), both players pass to reach the regroup phase, where the centralized turn-effect
# expiry strips the grant. SOR_164 no longer has Raid.

## GIVEN
CommonSetup: rrw/rrk/{}
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_156:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Raid
