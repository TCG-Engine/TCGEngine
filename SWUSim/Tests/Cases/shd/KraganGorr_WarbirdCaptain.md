# KraganGorr_EnemyAttacksBase_Shield
#// SHD_241 Kragan Gorr — "When an enemy unit attacks your base: Give a Shield token to a friendly unit in
#// the same arena as the attacker." P1 passes; P2's SHD_095 (ground) attacks P1's base; Kragan (the only
#// friendly ground unit) is shielded.
#// COVERAGE: offer=N/A (each fixture leaves exactly one friendly unit in the attacker's arena, so the
#//           arena filter narrows the pool to one and it auto-resolves — the auto-resolution IS the
#//           arena assertion in SpaceAttacker_ShieldsASpaceUnit_NotGroundKragan) ·
#//           decline=N/A (mandatory — no "you may") ·
#//           control=KraganGorr_EnemyAttacksBase_Shield (the trigger belongs to Kragan's controller and
#//           the Shield lands on a FRIENDLY unit while the ENEMY attacker gets nothing) ·
#//           boundary=KraganGorr_EnemyAttacksBase_Shield (base attack → Shield) vs
#//           EnemyAttacksAUnit_NoShield (unit attack → none), and ground vs space arena via
#//           SpaceAttacker_ShieldsASpaceUnit_NotGroundKragan ·
#//           reqboundary=N/A (the trigger resolves inside the opponent's attack with no P1 decision to
#//           serialize once the arena filter has narrowed the pool)

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_241:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# SpaceAttacker_ShieldsASpaceUnit_NotGroundKragan
#// SHD_241 — "a friendly unit in the SAME ARENA as the attacker". When the base-attacker is a SPACE unit
#// the pool is the friendly SPACE units only, so Kragan himself (ground) is not eligible even though he
#// is the ability's source: P1's lone space unit SOR_237 takes the Shield and Kragan takes none.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_241:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# EnemyAttacksAUnit_NoShield
#// SHD_241 — the trigger is "attacks YOUR BASE", not "attacks". P2's SHD_095 (2/3) attacks Kragan (6/6)
#// instead of the base: no Shield is handed out anywhere. Kragan takes the 2 counter-damage and SHD_095
#// is defeated by his 6.

## GIVEN
CommonSetup: yyk/yyk
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_241:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENACOUNT:0
