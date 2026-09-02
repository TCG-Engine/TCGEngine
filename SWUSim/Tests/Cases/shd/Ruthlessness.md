# Ruthlessness_DefeatDealsBaseDamage
#// SHD_143 Ruthlessness — Upgrade, cost 1, [Villainy][Aggression], trait Innate, +2/+0. Attached unit
#// gains: "When this unit attacks and defeats a unit: Deal 2 damage to the defending player's base."
#// COVERAGE: offer=N/A (no target pick — the damage always goes to the defending player's base) ·
#//           request boundary=N/A (the whole grant resolves inside the attack with no decision) ·
#//           control=N/A for a seat change; the "whose base" half is pinned by every section asserting
#//           P2BASEDMG while P1 owns the host ·
#//           boundary pair=Ruthlessness_DefeatDealsBaseDamage (defender dies → 2) +
#//           Ruthlessness_NoDefeat_NoBaseDamage (defender survives → 0) ·
#//           decline=N/A (mandatory, no "you may").
#// Host (SOR_046 3/7 + SHD_143 +2/+0 = 5 power) attacks and defeats SHD_095 (2/3); the grant then deals
#// 2 to P2's base.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# Ruthlessness_NoDefeat_NoBaseDamage
#// SHD_143 Ruthlessness — the base damage is gated on DEFEATING the defender. Host (5 power) attacks a
#// SOR_046 (7 HP) that survives → no defeat, so no base damage.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:0

---

# AttacksAndDefeats_FiresWhenTheATTACKERTradesAndDies
#// ⚠⚠ KNOWN ENGINE BUG — THIS SECTION IS EXPECTED TO BE RED. Restored 2026-09-01 from the SHD worklist,
#// where it had existed only as PROSE since 2026-08-15 because the old practice deleted failing sections
#// to keep the file green. It asserts the CORRECT behaviour.
#//
#// CR 16.c: an attack-end ability fires by DEFAULT even when its own unit is defeated by the combat
#// damage; requiring survival is a per-card OPT-IN. The authoritative roster already exists in the
#// engine (_SWUAttackEndRequiresSurvival, 21 cards) and SHD_143 is correctly ABSENT from it — so
#// Ruthlessness must still pay out on a trade.
#//
#// THE TRADE. The host is SOR_095 (3/3) wearing Ruthlessness for 5/3, pre-damaged to 2 so it has 1
#// remaining HP. It attacks SOR_046 Consular Security Force seeded at 4 damage (3/7, so 3 remaining):
#// the host's 5 defeats it, and the Consular's 3 counter-damage defeats the host. Both die in the same
#// combat, and the grant must still deal 2 to P2's base.
#// EXPECTED: P2BASEDMG 2. ACTUAL: 0 — nothing fires.
#// ROOT CAUSE: CollectAfterAttackTriggers blanket-returns at `if (SWUObjGone($attacker)) return;`
#// (CombatLogic.php ~1464), so the whole switch below it is survival-gated — the OPPOSITE of CR 16.c.
#// The few hooks that DO fire on death (Boba, ASH_013/016, LAW_046) are deliberately placed ABOVE it.
#// ⚠ NOT a one-liner: making the gate roster-aware drops the dead-attacker path into a switch whose
#// cases dereference $attacker and pass $attackerMzID, and a dead attacker's arena slot is REUSED by the
#// unit that reindexes into it. Every case needs a per-case audit.
#// The CONTROL is Ruthlessness_DefeatDealsBaseDamage above — the same grant, attacker surviving, green.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2
WithP1GroundArenaUpgrade: 0:SHD_143
WithP2GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:2
