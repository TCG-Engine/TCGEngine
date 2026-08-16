# ExhaustPerAspect
#// LAW_226 Secret Battle of Pretend (Cunning,Heroism event, cost 2) — "Exhaust a friendly unit. If you
#// do, for each different aspect it has, exhaust an enemy unit in the same arena." SOR_046 (Vigilance,
#// Heroism = 2 aspects) -> exhaust 2 enemy ground units.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# OneAspect_ExhaustOneEnemy
#// LAW_226 Secret Battle of Pretend — exhausting a friendly unit with ONE aspect exhausts exactly ONE
#// enemy unit in the same arena. SOR_164 Wampa (Aggression only) → P1 picks 1 of the 2 enemy ground units;
#// the unpicked one stays ready.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:READY

---

# SameArenaOnly_Space
#// LAW_226 Secret Battle of Pretend — the enemy exhaust is limited to the SAME arena as the friendly unit.
#// Exhausting SOR_237 Alliance X-Wing (Heroism, a SPACE unit) can only exhaust an enemy SPACE unit; the
#// enemy ground unit is never a legal target and stays ready.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: [SOR_178:1:0 SOR_225:1:0]
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:READY

---

# AlreadyExhaustedFriendly_NotOfferedAsATarget
#// LAW_226 Secret Battle of Pretend — "Exhaust a friendly unit. If you do, for each different aspect it
#// has, exhaust an enemy unit in the same arena."
#// SWUSim filters the friendly pool to READY units (a unit that cannot be exhausted is a zero-effect
#// target), so the already-exhausted SOR_164 is never offered: the lone ready SHD_029 auto-resolves and
#// the "if you do" DOES fire, leaving the enemy exhaust pending.
#// USER RULING (2026-08-14): the friendly pool stays READY-ONLY. A unit that cannot be exhausted is a
#// zero-effect target and is not selectable — SWUSim's standing convention — so "If you do" here covers
#// only the no-legal-target case. This is a DELIBERATE choice: the alternative reading would offer the
#// already-exhausted unit and let the rider fail.
#// ⚠ This section previously claimed that other behaviour and was SPURIOUSLY GREEN: its answer
#// `myGroundArena-0` was silently swallowed by the enemy multi-select, which was unvalidated until
#// MZMULTICHOOSE answers began being pool-checked (2026-08-14). It asserted nothing for months.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: [SOR_164:0:0 SHD_029:1:0]
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Exhaust_1_enemy_unit(s)_in_the_same_arena
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:READY

---

# NoEnemyInSameArena_NoEffect
#// LAW_226 Secret Battle of Pretend — the friendly unit is exhausted, but with no enemy unit in the same
#// arena the "if you do" resolves with no targets. SOR_164 Wampa (ground) exhausts; the only enemy unit is
#// in space and is unaffected.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY

---

# FewerEnemiesThanAspects_ExhaustWhatExists
#// LAW_226 Secret Battle of Pretend — it triggers even when there aren't enough enemy units to match the
#// aspect count. SOR_046 Consular Security Force (2 aspects) but only ONE enemy ground unit exists: that
#// single unit is exhausted (auto), and the space unit is untouched.

## GIVEN
CommonSetup: yyw/bgw/{myResources:2}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: LAW_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P2SPACEARENAUNIT:0:READY
