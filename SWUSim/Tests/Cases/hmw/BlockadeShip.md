# EnemyGroundAttacker_GetsMinusOne
#// HMW_251 Blockade Ship (Unit, Space, 5/8, cost 8, [Villainy], Separatist/Vehicle/Capital Ship,
#// non-unique) — "Sentinel / Enemy ground units get -1/-0 while attacking."
#//
#// COVERAGE: offer=N/A (structural: a continuous aura — no choice, no target pool, nothing to answer) ·
#//           decline=N/A (structural: nothing optional) ·
#//           boundary=FlooredAtZero_ANoPowerAttackerDealsNothing (the -1 cannot go below 0) paired with
#//           TwoBlockadeShips_Stack (it scales per copy) ·
#//           control=N/A (structural: "enemy" is recomputed from live control at damage time and the
#//           clause names no owner-scoped zone) ·
#//           reqboundary=N/A (structural: the aura holds no state at all — it is read inside the damage
#//           calculation of a single attack, so there is nothing written by one action and read by the
#//           next. Attacking IS the action, and the read happens within it) ·
#//           modes=2P only — "enemy" is a controller relation the shared helpers already resolve per
#//           seat, and there is no player reference, so all three formats share one code path.
#//
#// SENTINEL needs no code (HMW_251 is in $Sentinel_Cards).
#// ⚠ THE AURA IS CROSS-ARENA: the Blockade Ship sits in SPACE and debuffs the GROUND arena, so the
#// source's own arena is irrelevant and only the ATTACKER's is checked.
#// Here a 3/3 enemy ground unit attacks the base and deals 2, not 3.

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_251:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2

---

# EnemySpaceAttacker_IsUnaffected
#// HMW_251 — the ARENA negative. The clause says "enemy GROUND units", so an enemy SPACE attacker is
#// untouched and deals its full power. A debuff applied to every enemy attacker passes the positive
#// above and fails here.
#// ⚠ THE ATTACK IS AIMED AT THE BLOCKADE SHIP, NOT THE BASE — because the Ship's OTHER clause is
#// Sentinel, and it is in the space arena, so an enemy space unit is redirected onto it and CANNOT
#// reach the base at all. Written against the base first, this section read 0 base damage and looked
#// like the aura misfiring; it was the card's own first clause doing exactly what it says.
#// SOR_237 Alliance X-Wing is a 2/3: it deals its full 2 to the 5/8 Ship (1 if the aura leaked into the
#// space arena), and dies to the 5 coming back.

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_251:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P2>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_251
P1SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENACOUNT:0

---

# YourOwnGroundUnits_AreUnaffected
#// HMW_251 — the CONTROLLER negative. "ENEMY ground units" is relative to the Blockade Ship's own
#// controller, so P1's own ground attacker is NOT debuffed by P1's Blockade Ship. A debuff written for
#// "ground units" with no controller check would quietly punish its own side.
#// P1's 3/3 attacks P2's base for the full 3.

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_251:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# TwoBlockadeShips_Stack
#// ⚠ HMW_251 — it is NON-UNIQUE, and each copy is its own continuous effect, so two of them are -2/-0.
#// This is the HMW_145 Origin Tree Shyyyo lesson applied to an aura: count the copies rather than
#// breaking on the first one found.
#// The same 3/3 attacker now deals 1 instead of 2.

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_251:1:0
WithP1SpaceArena: HMW_251:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:1

---

# FlooredAtZero_ANoPowerAttackerDealsNothing
#// HMW_251 — the lower boundary. Power cannot be modified below 0, so a 1-power attacker into a single
#// Blockade Ship deals 0 rather than -1 (which would HEAL the base if it leaked through as a negative).
#// TWI_T01 Battle Droid is a plain 1/1 token: 1 - 1 = 0. Paired with the 3/3 above, the two pin the
#// arithmetic from both ends.
#// ⚠ NOT HMW_254 Captain Tarpals, which this section used first. He is a printed 0/2 — apparently the
#// perfect floor fixture — but he has RAID 2, so he attacks at 2 and dealt 1 through the debuff. The
#// printed power is not the attacking power whenever Raid is involved; read the whole text box of every
#// fixture, incidental ones included.
#// ⚠ Asserting P1BASEDMG:0 also proves the attack RESOLVED rather than being refused: a refused attack
#// and a zero-damage attack look identical on the base, which is why the attacker's exhausted state is
#// asserted too.

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_251:1:0
WithP2GroundArena: TWI_T01:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EnemyGroundDefender_IsNotReduced
#// HMW_251 — the "WHILE ATTACKING" negative. An enemy ground unit that is only DEFENDING is not debuffed,
#// so it strikes back at full power. P1 (the Blockade Ship's controller) attacks P2's SOR_095 Battlefield
#// Marine (3/3) with TWI_247 AT-TE Vanguard (6/9): the Marine dies and deals its full 3 back (2 if the
#// aura applied to every enemy ground unit in combat rather than to attackers only).
#// (The AT-TE's Restore 3 heals an undamaged base — inert here.)

## GIVEN
CommonSetup: rrk/yyk
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: HMW_251:1:0
WithP1GroundArena: TWI_247:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:TWI_247
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ReducedAttacker_DamageDependentHealUsesTheReducedDamage
#// HMW_251 — downstream read. ASH_031 Hera Syndulla, Renegade General (3/4): "When Attack Ends: If this unit
#// dealt combat damage to a base, heal that much damage from your base." P2's Hera attacks P1's base with
#// P1's Blockade Ship in play: she attacks at 2, deals 2, and so heals exactly 2 from P2's base (10 → 8),
#// not her printed 3. The unreduced control is ash/HeraSyndulla_RenegadeGeneral.md::HealBaseOnBaseHit.

## GIVEN
CommonSetup: rrk/yyk/{theirBaseDamage:10}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: HMW_251:1:0
WithP2GroundArena: ASH_031:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:8
