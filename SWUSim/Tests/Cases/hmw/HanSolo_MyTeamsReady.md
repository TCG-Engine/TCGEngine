# ReadiesAnotherFriendlyExhaustedUnit
#// HMW_170 Han Solo - My Team's Ready (Aggression/Heroism, Rebel/Official, cost 5, 4/7 Ground, unique) —
#// "Action [Exhaust]: Ready another unit."
#// COVERAGE: offer=OfferExcludesHimselfAndSpansBothSides (pool left pending — the only thing that shows
#//           BOTH the self-exclusion and that enemy units are in it) ·
#//           negative=NoOtherUnit_SoftPass_HanStillExhausts + CantReadyIsRespected ·
#//           boundary=ExhaustedHanCannotAct (the [Exhaust] COST gate: no ready Han, no action) ·
#//           control=StolenHan_ActsForTheNewController · reqboundary=RequestBoundary_AcrossTheTargetPick ·
#//           decline=N/A — "Ready another unit" prints no "may", so the target is a mandatory MZCHOOSE
#// ⚠ "ANOTHER UNIT" is unqualified, so it spans BOTH sides — readying an ENEMY unit is legal even though
#//   it is a pure drawback. Same rule as JTL_088 Phasma's "another First Order unit". The only thing
#//   "another" excludes is Han himself. CanReadyAnEnemyUnit + the offer section pin it.
#// ⚠ [Exhaust] is a COST, not a condition: with no other unit in play the Action is still usable and
#//   simply resolves to nothing, exhausting Han (the TS26_02 Anakin rule — conditions live in the
#//   handler, affordability is about paying the cost, and an exhaust-only action is a legal soft pass).
#//   NoOtherUnit_SoftPass_HanStillExhausts asserts that STATE rather than merely "nothing happened".
#// Here: an exhausted Battlefield Marine is readied and Han is left exhausted, having paid.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_170
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1NODECISION

---

# CanReadyAnEnemyUnit
#// HMW_170 — the unqualified reading, resolved rather than merely offered. The only other unit is an
#// exhausted ENEMY Dark Trooper and it really does get readied. A friendly-only implementation would
#// find no legal target here and soft-pass instead.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY

---

# OfferExcludesHimselfAndSpansBothSides
#// HMW_170 — the POOL, left pending. Two friendly units and one enemy are on the board; the pool must be
#// the Marine, the X-Wing and the Dark Trooper — and NOT Han, the one thing "another" excludes.
#// Three legal targets so nothing auto-resolves and there is a real offer to read.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP1GroundArena: SOR_095:0:0
WithP1SpaceArena: SOR_237:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&mySpaceArena-0&theirGroundArena-0
P1HASDECISION

---

# NoOtherUnit_SoftPass_HanStillExhausts
#// HMW_170 — the fizzle, asserted on STATE. Han is the only unit in play, so there is nothing to ready;
#// the Action still resolves, still costs its [Exhaust], and raises no prompt. An implementation that
#// gated this in SWUUnitActionAffordable would leave Han READY, which is the assertion separating them.
#// (Green before implementation — an absence guard; it stays meaningful as the cost proof.)

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:HMW_170
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:1
P1NODECISION

---

# AlreadyReadyUnitIsALegalTarget
#// HMW_170 — "a unit", not "an EXHAUSTED unit", so a unit that is already ready is a legal (if pointless)
#// choice: it stays ready and Han still pays. Mirrors the heal-an-undamaged-unit reading on HMW_063.
#// Two targets so the choice is real; the ready Marine is the one picked.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# ExhaustedHanCannotAct
#// HMW_170 — the [Exhaust] cost gate. Han starts EXHAUSTED, so the Action is unavailable: nothing is
#// readied, nothing is spent, no prompt. Without this the cost kind could be 'none' and every other
#// section would still pass.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:0:0
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION

---

# CantReadyIsRespected
#// HMW_170 — the outcome must be MEASURED, not assumed. SHD_193 Frozen in Carbonite reads "Attached unit
#// can't ready", and OnReadyCard enforces it — so choosing that unit resolves to nothing and it stays
#// exhausted. An implementation that wrote Status = 1 directly (rather than going through OnReadyCard)
#// would ready it anyway; that exact slip is on record for TS26_63 Rex's DC-17s.
#// A second legal target keeps the choice real so the frozen unit is genuinely chosen, not auto-picked.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP1GroundArena: SOR_095:0:0
WithP1GroundArenaUpgrade: 1:SHD_193
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# StolenHan_ActsForTheNewController
#// HMW_170 — the control cell. The Action belongs to whoever CONTROLS Han: P1 owns him, P2 controls him,
#// and P2 uses it to ready one of THEIR exhausted units. "Another" still excludes Han himself, and the
#// pool is read in the controller's frame — an owner-scoped collection would look at P1's board.

## GIVEN
CommonSetup: rrw/rrk/{}
WithActivePlayer: 2
WithP2GroundArenaControlled: HMW_170:1
WithP2GroundArena: SEC_080:0:0

## WHEN
- P2>UseUnitAbility:myGroundArena-1
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:CARDID:HMW_170
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# RequestBoundary_AcrossTheTargetPick
#// HMW_170 — the request-boundary cell. The target choice ends the request in production, so the chosen
#// unit must be re-resolved when the answer arrives rather than held from when the offer was raised.
#// Same flow and assertions as the first section with the boundary inserted before the pick.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: HMW_170:1:0
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SEC_080:0:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
