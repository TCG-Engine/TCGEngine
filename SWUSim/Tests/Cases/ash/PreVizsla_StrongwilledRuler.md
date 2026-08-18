# BudgetDefeat_CreatesPerDefeat
#// ASH_053 Pre Vizsla (Ground, 6/6, cost 8) — When Played: defeat any number of non-leader units with a
#// COMBINED 6-or-less remaining HP; create a Mandalorian token for each one defeated. P2 has two 3/1
#// Stormtroopers (combined 2 HP). P1 defeats both (one at a time), then the loop ends (Pre Vizsla's own
#// 6 HP no longer fits the reduced budget) → 2 Mandalorian tokens created.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_053

---

# DefeatNone_NoTokens
#// ASH_053 Pre Vizsla — "any number" may be zero. Declining defeats nothing and creates no Mandalorian
#// tokens (only Pre Vizsla enters; the enemy Stormtrooper lives).
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1

---

# DefeatSelf_OneToken
#// ASH_053 Pre Vizsla — she is a legal target of her own When Played (6/6 = exactly the 6-HP budget). With
#// no other units around, P1 selects Pre Vizsla herself: she is defeated and 1 Mandalorian token is created,
#// so only the token remains and Pre Vizsla is in the discard.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1

---

# DamagedHighHpUnit_RemainingHpFits
#// ASH_053 Pre Vizsla — the budget looks at REMAINING HP, not printed HP. An AT-ST (SOR_232, 6/7) with 1
#// damage has 6 remaining HP, so it fits the 6-or-less budget and can be defeated → 1 Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_232:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# DefeatFriendlyUnit_Token
#// ASH_053 Pre Vizsla — "any number of non-leader units" is not limited to enemies; a friendly unit can be
#// chosen too. P1 defeats their own Porg (LOF_254, 1/1) → 1 Mandalorian token; Pre Vizsla stays, Porg goes
#// to P1's discard.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP1GroundArena: LOF_254:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_053
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1DISCARDCOUNT:1

---

# LurkingTiePhantom_CannotBeDefeated_NoToken
#// ASH_053 Pre Vizsla — a unit that "can't be defeated by enemy card abilities" (Lurking TIE Phantom
#// SHD_187, 2/2) is not actually defeated even if chosen, so NO Mandalorian token is created for it. The
#// Phantom survives and Pre Vizsla enters alone.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_053

---

# Offer_NarrowsToWhatTheRemainingBudgetCanStillAfford
#// ASH_053 — "Defeat any number of non-leader units with a total of 6 or less remaining HP."
#// The offer is recomputed after every pick against the REMAINING budget, so a unit that no longer fits
#// drops out. Board is the reported scenario: remaining HP 4 (SOR_046 3/7 with 3 damage), 2 (SOR_108 1/2)
#// and 3 (SOR_095 3/3). Opening offer = everything that fits under 6 on its own.
#// After taking the 4, only 2 of budget is left, so the rem-3 unit must disappear and the rem-2 unit is
#// the only thing still selectable. Pre Vizsla himself (6/6) also drops out — he fits the opening 6 but
#// not the leftover 2. No existing section asserted the POOL, only the outcome.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0
P2GROUNDARENACOUNT:2

---

# Offer_OpeningPoolIsEverythingThatFitsSix
#// ASH_053 — the control for the section above: before any pick the whole 6 is available, so every unit
#// whose remaining HP is 6 or less is offered — both sides ("non-leader units" names no controller), and
#// Pre Vizsla himself (6/6, exactly 6). The 7-remaining SOR_046 (3/7 undamaged) is the one exclusion,
#// which is what proves the budget is being applied at all.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_108:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-1

---

# TwoDefeats_TwoShieldedTokens_ExactSixBudget
#// ASH_053 — 4 + 2 is exactly the whole budget, which exits the loop through the "budget spent" branch
#// rather than the "nothing left fits" branch. Both units die and TWO Mandalorian tokens are created,
#// each carrying the Shield its own Shielded keyword grants on creation (ASH_T01 is a 2/2 Shielded token).
#// This is the shape of bug report #972 ("did not get Mandalorian tokens on defeating two units").

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# StopEarly_OneDefeat_OneToken_BudgetLeftOver
#// ASH_053 — the player may stop at any point ("any number"). Taking only the rem-3 unit and declining
#// the next offer leaves 3 of the budget unspent, defeats ONE unit and creates exactly ONE token.
#// This is the user's fourth worked example, and the case that separates "any number" from "as many as
#// the budget allows".

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_108:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_108
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# TwoDefeats_ThenPASS_SurvivesTheRequestBoundaries
#// ASH_053 — the reported live symptom is "units get defeated, then I hit PASS and get no Mandalorian
#// tokens". Live, every pick is a SEPARATE HTTP request, so the running count has to survive the trip:
#// it rides the CUSTOM param ("ASH_053#0|<budget>|<count>"), and if it were ever kept in an in-memory
#// global instead it would come back as 0 in the next request and the finish step would create nothing —
#// exactly the reported shape. The harness runs the whole loop in one process, which is precisely the
#// axis a green suite cannot otherwise see, so the boundaries are inserted explicitly here.
#// Also uses the LIVE decline token: the UI's pass control submits `cardID=PASS`
#// (TryPassCurrentDecision in Core/UILibraries*.js), not the harness's choose-nothing '-'.
#// Two 3/1 Stormtroopers are defeated (1 + 1 of the 6 budget), a 3/7 stays out of reach at 7 remaining,
#// and PASS with budget still left must finish with BOTH tokens.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# PASS_WithTargetsStillInThePool_StillCreatesTheTokens
#// ⛔ Bug #972, reproduced from game 3342. Defeat two enemy units, then hit PASS while a legal target
#// REMAINS in the pool — the units stay defeated and NO Mandalorian tokens are created.
#// The pool is non-empty at the third offer because P1's own ASH_148 (8/7 with 5 damage → 2 remaining)
#// still fits the 2 left in the budget, so the player is asked again and declines with the Pass button.
#// ROOT CAUSE: a "PASS" answer is STICKY in the decision queue — ExecuteStaticMethods then SKIPS every
#// following CUSTOM that is not marked dontSkipOnPass (see SWUQueueAfterAction / _SWUQueueOrchestration,
#// which both carry the flag for exactly this reason). ASH_053's continuation IS the loop: its decline
#// branch is what calls _SWUAsh053Finish and creates one token per unit defeated so far. Skipped, the
#// defeats stand and the reward never happens.
#// ⚠ Why every earlier section missed it: declining with the harness's '-' is NOT sticky, and a PASS on
#// an EMPTY pool never reaches this CUSTOM at all (the loop had already finished itself). The bug needs
#// PASS *and* a non-empty pool together.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: ASH_148:1:5
WithP2GroundArena: ASH_129:1:2
WithP2GroundArena: HMW_115:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:4
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1
P1GROUNDARENAUNIT:3:CARDID:ASH_T01
P1GROUNDARENAUNIT:3:SHIELDCOUNT:1
