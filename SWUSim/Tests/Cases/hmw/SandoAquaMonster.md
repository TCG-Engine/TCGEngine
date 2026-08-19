# NabooBase_DefeatsUpToCombinedPowerFive_AndTakesThatMuch
#// HMW_094 Sando Aqua Monster (Vigilance, Creature, cost 8, 5/9 Ground, non-unique) —
#// "Grit / When Played: If you control a Naboo base, you may defeat any number of ground units with
#//  combined power equal to or less than this unit's power. Deal damage to this unit equal to the
#//  combined power of the defeated units."
#// COVERAGE: offer=OfferIsGroundOnly_ExcludesSpace + OfferSpansBothSidesAndIncludesItself ·
#//           negative=NoNabooBase_NoPromptAtAll + Decline_DefeatsNothing ·
#//           boundary=OverBudgetAnswerIsRejectedServerSide (5 is spendable, 6 is not) ·
#//           control=N/A — every half is unqualified ("ground units", "this unit"), and the Naboo gate
#//           reads the CONTROLLER's base which is already the acting seat · reqboundary=
#//           RequestBoundary_AcrossThePicks · decline=Decline_DefeatsNothing ("any number" includes none)
#// ⚠ THE BUDGET IS "THIS UNIT'S POWER", MEASURED WHEN THE OFFER IS BUILT — 5, since Sando enters
#//   undamaged. Grit (+1/+0 per damage) then RAISES his power as the self-damage lands, but that does
#//   NOT retroactively widen the budget: the choice was already made. This section shows both halves of
#//   that in one place — 2 + 3 = exactly 5 spent, 5 damage taken, and Grit reading him at 10 power after.
#// ⚠ SELF-INCLUSION: "any number of ground units" says neither "other" nor "enemy", and Sando is himself
#//   a ground unit in play when his own When Played resolves — so he IS in his own pool (engine rule:
#//   check whether the source meets its own condition). OfferSpansBothSidesAndIncludesItself pins it.
#//   Flagged as the one reading here I would want confirmed against the printed card.
#// ⚠ The self-damage counts only units ACTUALLY defeated — see CantBeDefeatedUnitContributesNothing.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_094
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:10
P1NODECISION

---

# NoNabooBase_NoPromptAtAll
#// HMW_094 — the gate. With a non-Naboo base the whole ability is skipped: no prompt, nothing defeated,
#// no self-damage. Sando sits at his printed 5/9 with the board intact.
#// (Green before implementation — an absence guard.)

## GIVEN
CommonSetup: bbw/rrk/{myBase:SOR_029;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:HMW_094
P1GROUNDARENAUNIT:2:DAMAGE:0
P1GROUNDARENAUNIT:2:POWER:5
P1NODECISION

---

# Decline_DefeatsNothing
#// HMW_094 — "you may … any number", so choosing NONE is a real answer: nothing is defeated and Sando
#// takes no damage. DONE is the multi-select terminator.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:DAMAGE:0
P1GROUNDARENAUNIT:2:POWER:5
P1NODECISION

---

# OfferIsGroundOnly_ExcludesSpace
#// HMW_094 — "GROUND units". A friendly X-Wing sits in the space arena and must not be in the pool,
#// even though its power (2) fits the budget comfortably. Left pending so the pool itself is read.
#// Sando is at ground index 2 (played last) and is in his own pool — see the next section.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2
P1HASDECISION

---

# OfferSpansBothSidesAndIncludesItself
#// HMW_094 — two readings pinned at once. "ground units" carries no controller restriction, so ENEMY
#// ground units are in the pool; and it says neither "other" nor "another", so SANDO HIMSELF is in it
#// (he is a ground unit in play by the time his own When Played resolves).
#// The enemy Consular Security Force (power 3) is in; the enemy X-Wing would be excluded by arena, and
#// is deliberately absent here so this section is only about side and self.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0
P1HASDECISION

---

# OverBudgetAnswerIsRejectedServerSide
#// HMW_094 — the budget is a SERVER rule, not a client convenience. The harness hands an answer straight
#// to the handler without consulting the offer or its cap, so an answer spending 3 + 3 = 6 against a
#// budget of 5 must be trimmed by SWUFilterBudgetAnswer: the first pick is paid for, the second is not.
#// Result: one Marine defeated, 3 self-damage, Sando at 8 power via Grit — and the Consular survives.
#// Without the server-side filter this section passes while the real offer was unenforced.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:1:CARDID:HMW_094
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:1:POWER:8

---

# CantBeDefeatedUnitContributesNothing
#// HMW_094 — "damage equal to the combined power of the DEFEATED units" must MEASURE the outcome, not
#// assume the attempt worked. JTL_103 Chewbacca (5/6) "can't be defeated … by enemy card abilities", so
#// picking him spends the whole budget and defeats nothing — and Sando must therefore take ZERO damage
#// and stay at his printed 5 power. An implementation that summed the picks' power rather than the
#// units it actually removed deals 5 to itself here.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103
P1GROUNDARENAUNIT:0:CARDID:HMW_094
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:POWER:5

---

# DefeatsAnEnemyGroundUnit
#// HMW_094 — the enemy half RESOLVES, not merely offered: an enemy Consular Security Force (power 3) is
#// defeated and Sando takes 3. It goes to its OWNER's discard pile, so P2's discard grows, not P1's.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:8

---

# RequestBoundary_AcrossThePicks
#// HMW_094 — the request-boundary cell, on a card that genuinely needs it: the budget, the weights and
#// Sando's own identity are all established when the offer is raised and consumed when the answer
#// arrives in a FRESH process. Anything held in memory (his mzID in particular — the arena reindexes as
#// units are defeated) is gone by then. Same flow and numbers as the first section.

## GIVEN
CommonSetup: bbw/rrk/{myBase:HMW_020;myResources:8}
P1OnlyActions: true
WithP1Hand: HMW_094
WithP1GroundArena: HMW_074:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_094
P1GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:POWER:10
