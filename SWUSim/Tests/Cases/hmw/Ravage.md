# AllThreeOnOneEnemy_PowerFloorsAtZero
#// HMW_071 Ravage ([Vigilance][Villainy], cost 4, Event, Disaster/Tactic, non-unique) —
#// "Distribute up to 3 Weakness tokens among any number of units."
#// COVERAGE: offer=OfferPendsStatingTheAmount (MZSPLITASSIGN has no SELECTABLEEXACT support, so the
#//           assertable form is the pending decision + its stated amount; the POOL is proven instead by
#//           what can be successfully ASSIGNED — SWUValidateDecisionAnswer rejects any mzID outside the
#//           offered spec, so FriendlyUnitsAreLegalTargets_Unqualified and SpaceAndGround_BothArenas
#//           each prove membership by landing a token there) ·
#//           negative=NoUnitsInPlay_CleanFizzleNoPrompt (nothing to distribute among) ·
#//           boundary=AllThreeOnOneEnemy_PowerFloorsAtZero (the full pool on one unit) vs
#//           UpTo_AssignFewer / UpTo_AssignZero (the "up to" lower half); the over-assign side is
#//           enforced server-side by SWUValidateDecisionAnswer's pool-total check ·
#//           control=ControlChanged_StolenUnitIsALegalTarget ·
#//           reqboundary=RequestBoundary_AcrossTheDistribute ·
#//           decline=UpTo_AssignZero_SoftPassStillPaysForTheEvent — per the "up to N" ruling the TARGET
#//           choice is mandatory and the soft pass is an AMOUNT of zero, so there is no separate
#//           decline branch to write.
#// ⚠ "any number of UNITS" carries no controller word and no arena word: every unit on the board is a
#//   legal target, friendly and enemy, ground and space. It also carries no per-unit limit — "any
#//   number of units" includes ONE unit, so all 3 may land on a single body.
#// ⚠ Weakness (HMW_T02) is a -1/-1 TOKEN Upgrade, so it stacks, it CEASES rather than going to a
#//   discard pile when its host leaves play, and the -1 HP is HP REDUCTION, not damage — unpreventable,
#//   shield-independent, and defeating only via the state-based shrink sweep.
#// Here SOR_046 Consular Security Force (3/7) takes all three: power 3-3 clamps to 0, HP 7-3 = 4.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:3
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:HMW_T02
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4
P2GROUNDARENACOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_071
P1NODECISION

---

# SplitTwoAndOne_AcrossTwoEnemies
#// HMW_071 — the distribute itself: 2 on one body, 1 on another. Quantity discrimination — an
#// implementation that gave every chosen unit the whole pool, or exactly one token each, produces
#// different numbers on both units.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:POWER:2
P2GROUNDARENAUNIT:1:HP:2
P1NODECISION

---

# FriendlyUnitsAreLegalTargets_Unqualified
#// HMW_071 — the unqualified-target cell, and the one that matters most on this card: "any number of
#// UNITS" names no controller, so your OWN units are legal targets (Ravage is a Disaster). The answer
#// is pool-validated server-side, so a friendly mzID landing a token IS the proof it was offered — a
#// friendly-only implementation would have this answer rejected outright.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:1,theirGroundArena-0:2

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# SpaceAndGround_BothArenas
#// HMW_071 — "units" is not arena-scoped either. One token to a space unit and two to a ground unit,
#// both on the enemy side. An implementation that collected only the ground arenas would have the
#// space half of this answer rejected.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirSpaceArena-0:1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2

---

# UpTo_AssignFewer
#// HMW_071 — "UP TO 3" means fewer is legal. Only one token is assigned; the other two are simply not
#// created (they do not spill onto anything, and nothing is held over).
#// ⚠ MEASURED, so nobody mistakes this for coverage of the flag: dropping the UPTO flag from the card
#//   changes NOTHING here — all 13 sections still pass. SWUValidateDecisionAnswer permits
#//   under-assignment on EVERY MZSPLITASSIGN regardless of the flag ("Under-assigning stays legal"),
#//   so the flag is a CLIENT confirm-button affordance and the harness renders no client. What this
#//   section really guards is the SERVER behaviour — that a partial assignment resolves correctly and
#//   the unassigned tokens evaporate — which is worth keeping. The flag itself is correct by
#//   inspection (same convention as SWUQueueDistributeAdvantage) and is not assertable here.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:POWER:3
P2GROUNDARENAUNIT:1:HP:3
P1NODECISION

---

# UpTo_AssignZero_SoftPassStillPaysForTheEvent
#// HMW_071 — the bottom of the "up to" range. Per the standing ruling, "up to N" has no declinable
#// TARGET: the soft pass is an amount of ZERO. Nothing is created — and the event is still paid for and
#// still in the discard, because the cost buys the ability, not the effect resolving.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_071
P1RESAVAILABLE:0
P1NODECISION

---

# Lethal_OneTokenDefeatsAOneHpUnit
#// HMW_071 — the -1 HP is HP REDUCTION, so a 1-HP body drops to 0 remaining HP and the state-based
#// shrink sweep defeats it. The token CEASES with its host rather than going to a discard pile, so
#// P2's discard holds the unit only.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
P1NODECISION

---

# Simultaneous_TwoOneHpUnitsBothDie
#// HMW_071 — ★ the index-shift cell. Two 1-HP units each take one token and both must die. If the
#// implementation defeated each unit as it applied its token, the first defeat would compact the arena
#// and the SECOND mzID (theirGroundArena-1) would resolve to the wrong slot — or to nothing — leaving a
#// survivor. Correct behaviour is apply-all-then-sweep-once, exactly as divided damage does.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: [SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:1,theirGroundArena-1:1

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:SOR_128
P2DISCARDUNIT:1:CARDID:SOR_128
P1NODECISION

---

# StacksOnAUnitThatAlreadyHasAWeakness
#// HMW_071 — Weakness is a non-unique token upgrade, so it stacks with one already attached. The
#// pre-seeded token makes the host 2/6; two more take it to 0/4 with three tokens on it.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:HMW_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:3
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4

---

# ControlChanged_StolenUnitIsALegalTarget
#// HMW_071 — a unit owned by P2 but controlled by P1 sits in P1's arena and is a legal target like any
#// other; the token attaches under the HOST's controller. The stat change applies to the unit wherever
#// it is, which is what "any number of units" means.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP1GroundArenaControlled: SOR_046:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0:3

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:4

---

# NoUnitsInPlay_CleanFizzleNoPrompt
#// HMW_071 — nothing to distribute among. No decision may be raised (a prompt with an empty pool is
#// the SEC_186 Garindan family), and the event still resolves to the discard having been paid for.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_071
P1RESAVAILABLE:0

---

# OfferPendsStatingTheAmount
#// HMW_071 — the offer, left PENDING. MZSPLITASSIGN is not covered by SELECTABLEEXACT, so what is
#// assertable here is that the decision exists on the caster's seat and that the prompt states the
#// real amount (the DamagePrompts_StateTheAmount convention) rather than only restating the rule.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Distribute_up_to_3_Weakness_tokens_among_any_number_of_units
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# RequestBoundary_AcrossTheDistribute
#// HMW_071 — the request-boundary cell. The distribute assignment is answered in a FRESH process, so
#// the pool size and the offered target list must both be reconstructible from serialized state.
#// Identical to SplitTwoAndOne_AcrossTwoEnemies plus one SimulateRequestBoundary line.

## GIVEN
CommonSetup: brk/bbw/{myResources:4}
P1OnlyActions: true
WithP1Hand: HMW_071
WithP2GroundArena: [SOR_046:1:0 SEC_080:1:0]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENAUNIT:1:POWER:2
