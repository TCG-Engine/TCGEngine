# LOF_215 on SOR_095: unit attacks P2 unit with Shield — Saboteur breaks Shield.
# Battlefield Marine (SOR_095, 3/3) with Ascension Cable (LOF_215, grants Saboteur)
# attacks P2's Battlefield Marine (3/3) which has a Shield (SOR_T02).
# Saboteur removes the shield before damage. Combat proceeds: P1 deals 3 to P2 unit (3HP) = death.
# P2 counters 3 to P1 unit (3HP) = P1 unit also dies. P2 unit goes to discard.

## GIVEN
CommonSetup: yyk/grw
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
