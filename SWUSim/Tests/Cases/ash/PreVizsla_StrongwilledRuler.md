# MultiSelect_FourPlusTwo_TwoShieldedTokens
#// ASH_053 Pre Vizsla (Ground, 6/6, cost 8) — When Played: defeat any number of non-leader units with a
#// COMBINED 6-or-less remaining HP; create a Mandalorian token for each one defeated.
#// Presented as ONE weighted multi-select (SWUQueueBudgetMultiChoose): a single modal with a live
#// "N of 6 HP left" counter, greying out whatever no longer fits, resolved by one Confirm.
#// User scenario 1 — board is remaining HP 4 (SOR_046 3/7 with 3 damage), 2 (SOR_108 1/2), 3 (SOR_095
#// 3/3). Picking the 4 leaves 2, which puts the rem-3 unit out of reach; picking the rem-2 unit spends
#// the budget exactly. Both die and TWO Mandalorian tokens are created, each with the Shield its own
#// Shielded keyword grants on creation (ASH_T01 is a 2/2 Shielded token).
#// Also the index-shift control for the batch: defeating theirGroundArena-0 compacts the arena, so a
#// resolver that re-read the raw mzIDs instead of re-resolving by UniqueID would hit the wrong unit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_053
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# MultiSelect_TwoPlusThree_TwoShieldedTokens
#// ASH_053 — user scenario 2, same 4/2/3 board. Taking the rem-2 unit first leaves 4, so BOTH the rem-4
#// and the rem-3 unit are still reachable; taking the rem-3 one leaves 1, which puts the rem-4 unit out.
#// Confirm defeats both picks → 2 Mandalorian tokens, each Shielded, with 1 HP of budget unspent.
#// This is the SHARP index-shift case: the picks are theirGroundArena-1 and -2, and defeating the first
#// slides the second down into slot 1. Resolving the batch by raw mzID would find nothing at -2 and
#// silently create only one token; the resolver converts every pick to a UniqueID before it defeats
#// anything.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# MultiSelect_ThreePlusTwo_OrderDoesNotMatter
#// ASH_053 — user scenario 3, same 4/2/3 board picked in the other order (rem-3 first, then rem-2).
#// A combined-budget effect must be order-independent: 3+2 and 2+3 both cost 5 of the 6, so the outcome
#// is identical to the section above. Order-dependence in a combined-cost effect is the classic tell of
#// a budget applied incrementally against a moving board rather than against one snapshot.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-2&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:CARDID:ASH_T01

---

# MultiSelect_SinglePick_OneToken_BudgetLeftOver
#// ASH_053 — user scenario 4: pick only the rem-3 unit and Confirm. "Any number" includes one, so the
#// player may stop with budget unspent: ONE unit defeated, exactly ONE Shielded Mandalorian token, and
#// the other two enemy units survive. This is the case that separates "any number" from "as many as the
#// budget allows" — and, in the old one-at-a-time loop, the case that needed a decline to reach at all.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:2
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# Offer_OpeningPoolAndPerUnitWeights
#// ASH_053 — the offer itself. Before any pick the whole 6 is available, so every non-leader unit whose
#// remaining HP is 6 or less is offered: both sides ("non-leader units" names no controller), and Pre
#// Vizsla himself at exactly 6. The 7-remaining SOR_046 (3/7 undamaged) is the one exclusion, which is
#// what proves the budget is applied to the pool at all.
#// The tooltip assertion pins the "~BUDGET~<total>~<label>~<mzID>=<weight>…" side channel that carries
#// the per-unit weights to the modal (Core/MZMultiChooseUI.js). That channel is the ONLY thing the client
#// can compute its reactive greying-out from — remaining HP is not derivable from a CardID — so a wrong
#// or missing weight here is a silently wrong UI that no board-state assertion would ever see.
#// The decision is deliberately left unanswered so it is still pending to read.

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
P1DECISIONTOOLTIP:Defeat_any_number_of_non-leader_units_with_6_or_less_combined_remaining_HP~BUDGET~6~HP~myGroundArena-0=3~myGroundArena-1=6~theirGroundArena-1=2

---

# OverBudgetPicks_AreDroppedServerSide
#// ASH_053 — the modal's greying-out is UX, never enforcement: the schema harness feeds an answer
#// straight to the handler, and so could a hand-built request. Submitting ALL THREE of the 4/2/3 board
#// (total 9) must not defeat 9 HP worth of units. The resolver re-measures the board and re-applies the
#// budget in submitted order — 4 fits (2 left), 2 fits (0 left), 3 does not — so the rem-3 unit survives
#// and only TWO tokens are created.
#// Without this re-validation the "answer + assert the outcome" sections above would all pass even if the
#// engine had offered a wrong pool or no cap at all.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:3
WithP2GroundArena: SOR_108:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:CARDID:ASH_T01

---

# BudgetDefeat_CreatesPerDefeat
#// ASH_053 — two 3/1 Stormtroopers (1 remaining HP each, combined 2). Both are taken in one Confirm and
#// TWO Mandalorian tokens are created. Pre Vizsla's own 6 HP would have fit the opening budget but not
#// alongside them, and he is not picked.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_053

---

# DefeatNone_NoTokens
#// ASH_053 — "any number" may be zero. Confirming with nothing selected (the modal submits '-') defeats
#// nothing and creates no Mandalorian tokens; only Pre Vizsla enters and the enemy Stormtrooper lives.
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

# PASS_DefeatsNothingAndCreatesNothing
#// ⛔ Bug #972 regression, from game 3342: units were defeated and NO Mandalorian tokens appeared.
#// ROOT CAUSE (old design): the effect was a re-offered loop whose CONTINUATION created the tokens, and a
#// "PASS" answer is STICKY — ExecuteStaticMethods skips every following CUSTOM not marked dontSkipOnPass.
#// Passing after two defeats therefore skipped the payoff while the defeats stood.
#// The bug is now structurally impossible: the defeats and the tokens live in the SAME handler, so there
#// is no in-between state to strand. This section pins the atomicity — a PASS resolves to "chose nothing",
#// which must leave the board untouched: both enemy units alive, no tokens, Pre Vizsla alone.
#// The live decline token is asserted deliberately: the UI's pass control submits `cardID=PASS`
#// (TryPassCurrentDecision in Core/UILibraries*.js), NOT the harness's choose-nothing '-', and the two
#// travel different paths through the queue.

## GIVEN
CommonSetup: bbk/rrk/{myResources:8}
P1OnlyActions: true
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: ASH_053

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENACOUNT:2
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_053

---

# Confirm_SurvivesTheRequestBoundary
#// ASH_053 — live, the offer and the Confirm are SEPARATE HTTP requests, so everything the resolver needs
#// has to survive serialization: the budget rides the CUSTOM param and the picks arrive in the answer.
#// Nothing may be held in an in-memory global, which would come back empty in the next request and create
#// zero tokens — exactly the shape reported in #972. The harness runs a whole action in one process, so
#// this axis is invisible to a green suite unless the boundary is inserted explicitly.

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
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:2:CARDID:ASH_T01
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1

---

# DefeatSelf_OneToken
#// ASH_053 — she is a legal target of her own When Played (6/6 = exactly the 6-HP budget). With no other
#// units around, P1 selects Pre Vizsla herself: she is defeated and 1 Mandalorian token is created, so
#// only the token remains and Pre Vizsla is in the discard.
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
#// ASH_053 — the budget looks at REMAINING HP, not printed HP. An AT-ST (SOR_232, 6/7) with 1 damage has
#// 6 remaining HP, so it fits the 6-or-less budget and can be defeated → 1 Mandalorian token.
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
#// ASH_053 — "any number of non-leader units" is not limited to enemies; a friendly unit can be chosen
#// too. P1 defeats their own Porg (LOF_254, 1/1) → 1 Mandalorian token; Pre Vizsla stays, Porg goes to
#// P1's discard.
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
#// ASH_053 — a unit that "can't be defeated by enemy card abilities" (Lurking TIE Phantom SHD_187, 2/2)
#// is not actually defeated even when chosen, so NO Mandalorian token is created for it: the tokens are
#// "for each unit defeated this way", not for each unit selected. The resolver counts SWUDefeatUnit's
#// return value rather than the size of the pick list, so the Phantom survives and Pre Vizsla enters
#// alone with nothing else on the board.
#// ⚠ Under the old one-at-a-time loop this section could not actually observe the token rule — the run
#// ended on a still-pending re-offer, so the finish step that creates tokens was never reached and an
#// over-counting bug would have passed here.

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
