# AttackedUnit_NotEligible
#// SEC_177 It's Not Over Yet — a unit that attacked this phase is NOT eligible to ready. SOR_095
#//   attacks the base (exhausted + marked attacked-this-phase); then SEC_177 offers no ready target, so
#//   the unit stays exhausted and only the Spy is created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_177

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1NODECISION

---

# ReadyEligible_CreateSpy
#// SEC_177 It's Not Over Yet (Event, cost 2, Aggression) — "You may ready a unit that didn't attack or
#//   enter play this phase. Create a Spy token." A GIVEN exhausted SOR_095 (not played/attacked this
#//   phase) is eligible → ready it; also create a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENACOUNT:2
P1NODECISION

---

# PassReadyButStillCreateSpy
#// SEC_177 It's Not Over Yet — the ready is a "you may", but the Spy is not optional. With an eligible
#//   exhausted SOR_095 present, P1 declines the ready: SOR_095 stays exhausted, yet the Spy token is
#//   still created.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
P1NODECISION

---

# UnqualifiedPool_OffersAnOpponentsUnitToo
#// SEC_177 reads "You may ready A UNIT that didn't attack or enter play this phase" — UNQUALIFIED, so
#// per CR it names no controller and spans the WHOLE TABLE, opponents included. USER RULING 2026-08-25.
#// This was previously narrowed to the caster's own board — wrong in 2-player, not just Team Suns.
#// ⚠ The three sections above all have an EMPTY opponent board, which is exactly why the narrowing
#// survived: with nobody else's units in play, a self-only pool and a table-wide pool are the same set.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# OpponentsAttackedUnit_IsExcluded
#// THE DISCRIMINATOR for the per-controller flag read. SWU_UNIT_ATTACKED_{uid} is stored on the unit's
#// CONTROLLER, so reading it against the CASTER returns false for every foreign unit — an opponent's
#// just-attacked unit would look eligible. P2 attacks with its unit, so only P1's own exhausted unit
#// (which did not attack) may be readied.
#// ⚠ Reverting SWUUnitAttackedThisPhase() to GlobalEffectCount($player, …) reds exactly this section.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SOR_095:0:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_177

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0

---

# PassReadyButStillCreateSpy_ByConfirmingEmpty
#// ⚠ PASS-TWIN of PassReadyButStillCreateSpy — byte-for-byte identical except the decline.
#// `-` and "PASS" are two DIFFERENT declines, and the client only ever submits "PASS" (all three decline
#// paths in Core/UILibraries*.js). Historically every decline test here answered `-`, so the path players
#// actually take was untested. This continuation (SEC_177#0) is one that does more than apply the pick, and
#// it now runs on a decline because SWUQueueMayChooseTarget defaults dontSkipOnPass to 1 — this twin is
#// what covers that. If the two declines ever diverge, one of the pair goes red.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Hand: SEC_177

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
P1NODECISION
