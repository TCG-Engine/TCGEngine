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
