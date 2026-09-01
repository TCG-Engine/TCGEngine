# Undamaged_SentinelForcesAttackOntoIt
#// SOR_048 Vigilant Honor Guards (Ground, 4/6, Vigilance/Heroism) — "While this unit is undamaged,
#//   it gains Sentinel." P2's undamaged Guards are the only legal target for P1's Consular Security
#//   Force (3/7): the declared BASE attack auto-resolves onto them (pool of one). The Guards take 3
#//   and counter for 4 — and having taken damage, they are NOT Sentinel at end-state.
#// COVERAGE: offer=Damaged_NoSentinel_BaseAttackable (with the gate OFF the base is back in the
#//           attack-target pool — the explicit BASE answer would throw if out of pool) + this section
#//           (gate ON narrows the pool to the Guards alone; the auto-resolution IS the assertion) ·
#//           reqboundary=HealedToFull_RegainsSentinel (the gate is re-evaluated across three separate
#//           serialized actions: play, heal pick, enemy attack) · control=N/A (the gate reads only the
#//           unit's own Damage; Sentinel protects whichever side controls it by definition) ·
#//           boundary pair=Undamaged_KeywordOn vs Damaged_NoSentinel_BaseAttackable (0 damage vs any
#//           damage) · decline=N/A (static ability — no "you may" on the card)
#// COVERAGE (Phase C update): offer=AttackTargetPool_UndamagedNarrowsToOne (the pool is read directly
#//           as a count — exactly 1 target — instead of inferred from where an attack landed), with
#//           AttackTargetPool_DamagedWidensToThree as its 1-vs-0-damage partner · control=NO LONGER
#//           N/A: StolenGuards_SentinelGuardsTheNewControllersBase proves the gate is read from the
#//           CONTROLLER (a stolen, undamaged Guards guards its new controller's base against its owner)
#//           · boundary pair also=ShieldAbsorbsTheHit_GuardsStayUndamagedAndKeepSentinel vs
#//           SentinelDropsAfterFirstHit_SecondAttackerReachesBase (prevented damage never sets a damage
#//           counter, so the gate never flickers; the identical unshielded flow lets attacker #2 through)
#//           · scope=ArenaScope_SpaceAttackerIgnoresTheGroundSentinel and
#//           Sentinel_ProtectsTheOtherFriendlyUnitToo (arena scope, and allies are covered too)

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_048:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# Undamaged_KeywordOn
#// An undamaged Vigilant Honor Guards has Sentinel (static keyword check, no combat).

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 1
WithP2GroundArena: SOR_048:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# Damaged_NoSentinel_BaseAttackable
#// With 1 damage on the Guards the gate is OFF: the base is a legal attack target again, so the
#//   Consular Security Force hits it for 3 and the Guards are untouched.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_048:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# HealedToFull_RegainsSentinel
#// Healing the Guards back to 0 damage restores the gate: P2 plays Repair (heal 3) on the 3-damage
#//   Guards, and P1's follow-up attack is forced onto them again (pool of one) — they end at 3 damage
#//   from the new hit, the attacker takes 4, the base stays clean.

## GIVEN
CommonSetup: bbw/bbw/{theirResources:1;theirhandCardIds:SOR_074}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_048:1:3

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# SentinelDropsAfterFirstHit_SecondAttackerReachesBase
#// The gate toggles mid-phase: the first attacker is forced onto the undamaged Guards (now damaged),
#//   so the second attacker's declared BASE attack goes through for 3.

## GIVEN
CommonSetup: bbw/bbw
WithP1GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_048:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:1:DAMAGE:0

---

# Sentinel_ProtectsTheOtherFriendlyUnitToo
#// SOR_048 Vigilant Honor Guards — the reminder text is "can't attack your non-Sentinel UNITS or your
#// base", so the gate shields the whole side, not just the Guards. P2 fields the undamaged Guards
#// alongside a bare Battlefield Marine; P1's Consular Security Force declares an attack on the base and
#// has exactly one legal target, so it auto-resolves onto the Guards. The Marine ends untouched — the
#// assertion a self-protection-only reading of Sentinel would fail while still passing every existing
#// base-redirect section.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_048:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# AttackTargetPool_UndamagedNarrowsToOne
#// SOR_048 Vigilant Honor Guards — the OFFER axis read directly, with no combat to consume it. With the
#// undamaged Guards and a bare ally on P2's board, the valid-target count for P1's ground attacker is
#// exactly 1: the base and the non-Sentinel ally are both removed from the pool, leaving only the
#// Guards. Reading the count instead of an outcome is what distinguishes "the pool narrowed" from
#// "the attack happened to land there".

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_048:1:0 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
ATTACKTARGETS:1:G:0:1

---

# AttackTargetPool_DamagedWidensToThree
#// The N-vs-N-1 partner of AttackTargetPool_UndamagedNarrowsToOne, on the same board with a single
#// point of damage on the Guards. The gate is off, so the pool goes back to its full size: the enemy
#// base plus both enemy ground units — three targets, not one. One damage is the whole difference.

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_048:1:1 SOR_095:1:0]

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
ATTACKTARGETS:1:G:0:3

---

# ArenaScope_SpaceAttackerIgnoresTheGroundSentinel
#// SOR_048 Vigilant Honor Guards — "Units in THIS ARENA can't attack…". The gate binds ground attackers
#// only: P1's space TIE fighter is in the other arena, so P2's base is still a legal target and takes
#// the full 2 while the undamaged ground Guards keep Sentinel and take nothing. Pairs with
#// Undamaged_SentinelForcesAttackOntoIt, where the same Sentinel DOES bind a ground attacker.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_048:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ShieldAbsorbsTheHit_GuardsStayUndamagedAndKeepSentinel
#// SOR_048 Vigilant Honor Guards — the sharp edge of "while this unit is UNDAMAGED". A Shield token
#// PREVENTS the damage rather than healing it afterwards, so the Guards never take a damage counter and
#// the gate never flickers off. P1's first attacker is redirected onto the shielded Guards, the shield
#// pops, the Guards are still at 0 damage and still Sentinel — so P1's SECOND attacker is redirected
#// onto them too and its declared base attack deals nothing to the base. Contrast
#// SentinelDropsAfterFirstHit_SecondAttackerReachesBase, the identical flow without the shield, where
#// the second attacker gets through for 3.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: [SOR_046:1:0 SOR_095:1:0]
WithP2GroundArena: SOR_048:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# StolenGuards_SentinelGuardsTheNewControllersBase
#// SOR_048 Vigilant Honor Guards — the CONTROL axis, verifying the ledger's old "N/A by definition"
#// claim instead of trusting it. The Guards are OWNED by P2 but CONTROLLED by P1 (the end state after a
#// take-control effect), and undamaged. "your non-Sentinel units or your base" is read from the
#// CONTROLLER, so the stolen Guards must guard P1's base against their own owner: P2's declared base
#// attack is redirected onto them. A seat-bound reading of the gate would leave P1's base exposed here
#// while passing every other section in this file. (End-state note: having just taken the 3, the Guards
#// are damaged and so are correctly no longer Sentinel by the time the assertions are read; the
#// 3/3 attacker took the Guards' 4 back and is gone.)

## GIVEN
CommonSetup: bbw/bbw
WithActivePlayer: 1
WithP1GroundArenaControlled: SOR_048:2
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENACOUNT:0
