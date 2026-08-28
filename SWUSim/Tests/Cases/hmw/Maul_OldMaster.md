# Front_PlaysAUnitFromHandAtOneLess_ThenDefeatsIt
#// COVERAGE: offer=Front_Offer_OnlyAffordableHandUnits_EventsExcluded
#//                 + Deployed_Offer_OnlyUnitsDefeatedThisPhase
#//           decline=Front_Decline_LeaderStillExhausted_NothingPlayed
#//                 + Deployed_Decline_NothingIsPlayed
#//                 (+ the DISTINCT cannot-pay branches: Front_UnaffordableEvenAtOneLess_IsNotOffered,
#//                    Deployed_NothingWasDefeatedThisPhase_NoPrompt)
#//           boundary=Front_TheDiscountIsOneAtASecondCostPoint (two cost points pin "1 less");
#//                    Deployed_TheDiscountIsFive_AndFloorsAtZero
#//           control=N/A (a LEADER cannot be taken control of — every take-control effect reads
#//                   "non-leader unit" — and neither side names an owner-scoped zone belonging to
#//                   anyone but the leader's controller: "your hand", "your discard pile")
#//           reqboundary=Front_SimulateRequestBoundary_StillPlaysAndDefeats
#//                     + Deployed_SimulateRequestBoundary_StillPlays
#//           modes=2P only (no player reference, no friendly/enemy wording — "your hand" and
#//                 "your discard pile" are self-scoped, so all three formats share one code path)
#//           epic-deploy=N/A (fully generic: SWUDeployLeader gates on the leader's PRINTED COST, which
#//                 IS the "7 or more resources" threshold, and core/Palpatine_Deploy_BelowThreshold
#//                 already guards it — no per-leader code exists to test)
#//
#// ════════════════════════════════════════════════════════════════════════════════════════════════
#// HMW_016 Maul - Old Master (Leader, Cunning+Villainy, Force/Fringe, unique, cost 7; deployed 5/6 Ground)
#//
#//   FRONT   Action [Exhaust]: Play a unit from your hand. It costs [1 resource] less. Then, defeat it.
#//           (When Played abilities resolve after the unit is defeated.)
#//           Epic Action: If you control 7 or more resources, deploy this leader.
#//
#//   DEPLOYED  Shielded
#//             When Deployed: You may play a unit that was defeated this phase from your discard pile.
#//             It costs [5 resources] less.
#//
#// SEC_018 DJ is the direct sibling — same Action shape, same -1, same parenthetical, differing only in
#// "the chosen unit captures it" vs "defeat it". Its uniqueness-deferral machinery is reused verbatim.
#// ════════════════════════════════════════════════════════════════════════════════════════════════
#//
#// The composite front positive, and it deliberately uses an OFF-ASPECT unit so the reduction is shown
#// composing with the aspect penalty rather than replacing it: SEC_080 Imperial Dark Trooper is a
#// vanilla 2-cost 3/3, but its Command pip is uncovered by a Cunning base and Maul's Cunning/Villainy
#// icons, so it prices at 2+2=4 and costs 3 here. Three of six resources survive; the unit enters play
#// and is then defeated, landing in P1's discard.
#// The leader ends EXHAUSTED — the Action's cost — and P1 keeps no unit for it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
P1RESAVAILABLE:3
P1NODECISION

---

# Front_TheDiscountIsOneAtASecondCostPoint
#// A single cost point pins a discount only loosely. ASH_242 Death Trooper Squad is a vanilla 4-cost
#// Villainy 5/4, on-aspect under a Cunning base and Maul's own Cunning/Villainy icons, so it costs 3
#// here and leaves 3 of 6. With the 2->1 case above this rules out every constant but 1.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: ASH_242

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_242
P1RESAVAILABLE:3

---

# Front_TheDefeatedUnitsWhenPlayedStillRESOLVES
#// The half of the parenthetical that is directly observable: the defeat must not SWALLOW the played
#// unit's When Played. It is queued when the unit enters and resolves afterwards, so it still happens
#// even though the unit is already in the discard by then.
#// SOR_111 Patrolling V-Wing is a 2-cost Command 1/1 whose entire text is "When Played: Draw a card",
#// so the draw is the receipt. Command is off-aspect here (+2), making it cost 4 and so 3 after the
#// reduction. Deck 3 -> 2 and a card in hand prove the trigger fired; the discard proves it still died.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: SOR_111
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_111
P1HANDCOUNT:1
P1DECKCOUNT:2
P1RESAVAILABLE:3

---

# Front_WhenPlayedResolvesAFTERTheDefeat_ASelfReferenceFizzles
#// THE ORDERING ITSELF. Most When Played abilities produce the same end state either way, which is why
#// the card prints the reminder at all — so the discriminating fixture has to be one whose ability
#// needs the played unit to STILL BE IN PLAY.
#//
#// SEC_056 Escape Pod ("When Played: You may have THIS UNIT capture a friendly non-Vehicle, non-leader
#// unit") is exactly that. Under the printed order Escape Pod is already defeated when its trigger
#// resolves, so there is no captor and the friendly SEC_080 is untouched. Resolve the trigger FIRST and
#// SEC_080 would be captured — it would leave the arena as a subcard, which the arena count sees.
#// Escape Pod is Vigilance (off-aspect, +2) so it costs 3, and 2 after the reduction.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SEC_056

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_056

---

# Front_Decline_LeaderStillExhausted_NothingPlayed
#// ★ USER RULING: playing a card FROM HAND is ALWAYS declinable, printed "may" or not — the hand is a
#// HIDDEN zone, so a player can never be forced to reveal that they held a playable card. Maul's front
#// says only "Play a unit from your hand", and it is still an MZMAYCHOOSE.
#// ★ And declining does NOT refund the cost: the Action's price is the exhaust, which buys the ABILITY,
#// not the effect resolving. The leader stays exhausted with the card still in hand.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESAVAILABLE:6
P1NODECISION

---

# Front_EmptyHand_NoPrompt_ButTheActionStillCostsTheExhaust
#// An exhaust-only leader Action is ALWAYS available — it is a legitimate SOFT PASS (the Thrawn ASH_004
#// ruling), so its condition must live in the handler and NEVER in SWULeaderActionAffordable. Gating it
#// there would make the action vanish from the menu instead of resolving to nothing.
#// With an empty hand there is nothing to offer and no prompt appears, but Maul is still exhausted.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:0
P1RESAVAILABLE:6
P1NODECISION

---

# Front_UnaffordableEvenAtOneLess_IsNotOffered
#// CANNOT-PAY IS A DIFFERENT BRANCH FROM DECLINE. LOF_236 Army of the Dead is a vanilla 6-cost Villainy
#// unit, so it still costs 5 after the reduction — more than the 4 P1 holds. There is no legal target,
#// so no prompt is raised rather than one that could only fizzle.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: LOF_236

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4
P1NODECISION

---

# Front_Offer_OnlyAffordableHandUnits_EventsAreExcluded
#// THE OFFER CELL for the front side, with two independent exclusion reasons on one hand so neither can
#// cover for the other. P1 holds 4 resources and:
#//   • SEC_080 (2 -> 1)   legal
#//   • ASH_242 (4 -> 3)   legal        <- two legal targets, so the pool survives to be inspected
#//   • LOF_236 (6 -> 5)   EXCLUDED, beyond reach even at the discount
#//   • SOR_251 Confiscate — an EVENT, EXCLUDED by "play a UNIT from your hand"

## GIVEN
CommonSetup: yyk/bbw/{myResources:4;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: ASH_242
WithP1Hand: LOF_236
WithP1Hand: SOR_251

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# Front_SecondCopyOfAUnique_TheDefeatWaitsForTheUniquenessChoice
#// THE HARD CASE, inherited wholesale from SEC_018 DJ where it was a live bug.
#//
#// CR 1050.3: playing a second copy of a unique card forces its controller to defeat one of them, and
#// that defeat happens IMMEDIATELY as a game rule. ActivateCard can only QUEUE that choice because it
#// is interactive — so defeating inline here would jump the queue, re-indexing the arena underneath a
#// pending positional offer. The pick would silently no-op and the MANDATORY uniqueness defeat would be
#// skipped entirely, leaving the player with two copies of a unique unit.
#//
#// P1 already controls LOF_093 Gungi (a vanilla unique, so nothing else muddies the result) and
#// plays a second copy for 5 — both its Command and Heroism pips are uncovered here (+4). P1 answers the uniqueness choice by defeating
#// the OLD copy, and Maul's own defeat then resolves against the new one — so both are gone and the
#// arena is empty, which is the only outcome consistent with the rules.
#//
#// ⚠ THIS SECTION ALONE DOES NOT PROVE THE DEFERRAL — measured, not assumed. Defeating the OLD copy
#// ends with both units gone whether the deferral is there or not, so removing it leaves this green.
#// The Front_SecondCopyOfAUnique_DefeatingTheNEWCopy section below is the discriminating one.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: LOF_093:1:0
WithP1Hand: LOF_093

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P1NODECISION

---

# Front_SimulateRequestBoundary_StillPlaysAndDefeats
#// THE REQUEST-BOUNDARY CELL for the front side. The hand choose ends the request in production, so the
#// continuation that plays the unit, locates it and defeats it runs in a FRESH process — anything the
#// offer parked in an in-memory global would be gone and the handler would return silently, leaving the
#// leader exhausted for nothing.
#// Two legal hand units so a real decision is pending for the boundary to interrupt.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: SEC_080
WithP1Hand: ASH_242

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
P1HANDCOUNT:1
P1RESAVAILABLE:3

---

# Front_TurnPassesToTheOpponent_TheNestedPlayGrantsNoExtraAction
#// ⚠ EVERY OTHER SECTION IN THIS FILE IS BLIND TO THIS. P1OnlyActions claims initiative and auto-passes
#// the opponent, so the turn returns to P1 whether one after-action ran or two.
#// The nested ActivateCard that plays the hand unit runs its OWN after-action on top of the leader
#// Action's. Unneutralised, the turn swaps twice and P1 silently gets a FREE EXTRA ACTION — a defect
#// measured live on HMW_204 during this same run, where twelve green sections could not see it.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
WithActivePlayer: 1
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SEC_080
TURNPLAYER:2

---

# Front_TurnPassesToTheOpponent_EvenWhenDECLINED
#// The control for the section above: declining must also leave exactly one after-action, or a fix that
#// only corrected the accept path would look complete while the decline path still double-advanced.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
WithActivePlayer: 1
WithP1Hand: SEC_080

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:-

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
TURNPLAYER:2

---

# Deployed_HasShieldedAndEntersWithAShieldToken
#// ── DEPLOYED SIDE ───────────────────────────────────────────────────────────────────────────────
#// Shielded is derived by the generator from deployTextData into $Shielded_Cards, so this needs no
#// code — but the deployed side is a SEPARATE ability set that has to clear the bar on its own, and a
#// leader whose keyword silently failed to register would look identical everywhere else.
#// Deploying is free beyond the 7-resource threshold, so the 8 resources are untouched.
#//
#// ⚠ THE DEPLOY RAISES TWO ENTRY TRIGGERS — Shielded and the When Deployed — so the engine asks which
#// to resolve first (MZCHOOSE over EffectStack-0/1, "Choose_trigger_to_resolve"). That is CR-correct
#// and it is why every deployed section here answers EffectStack-0 straight after DeployLeader. A
#// section that omits it fails with its next answer landing on the ordering prompt instead.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_016
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:6
P1RESAVAILABLE:8

---

# Deployed_WhenDeployed_PlaysAUnitDefeatedThisPhaseAtFiveLess
#// The deployed side's own ability. P1 first trades SOR_128 Death Star Stormtrooper (a 3/1) into an
#// enemy 3/3, which puts a P1-OWNED unit in P1's discard flagged as defeated THIS PHASE. P1 then
#// deploys Maul, and the When Deployed offers that unit back at 5 less.
#// SOR_128 costs 2 and is Villainy (on-aspect), so 2 - 5 floors at 0: it comes back free, and the 8
#// resources are still untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0
P1RESAVAILABLE:8
P1NODECISION

---

# Deployed_TheDiscountIsFive_NotFloored_WhenTheUnitCostsMore
#// The other end of the reduction: LOF_236 Army of the Dead is a vanilla 6-cost Villainy unit, so it
#// costs exactly 1 here rather than 0. Together with the free 2-cost case above this pins "5 less"
#// and proves the floor is a clamp rather than the rule.
#// LOF_236 is put in the discard by DEFEATING it this phase — a 7/6 pre-damaged to 1 remaining HP
#// trades into a 3/3, which both kills it and stamps the defeated-this-phase flag a seeded discard
#// could never carry.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: LOF_236:1:5
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:1
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_236
P1DISCARDCOUNT:0
P1RESAVAILABLE:7

---

# Deployed_OnlyUnitsDefeatedTHISPhaseAreOffered
#// THE NEGATIVE for the deployed side's gate, and the one that a "play any unit from your discard"
#// implementation passes every other deployed section on.
#// The discard is SEEDED with ASH_242 — present, affordable, a unit, but never defeated this phase —
#// while SOR_128 genuinely dies in combat. Only SOR_128 may be offered, so the pool is exactly one
#// entry and the seeded card is untouched.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Discard: ASH_242

## WHEN
- P1>AttackGroundArena:0:0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-1

---

# Deployed_NothingWasDefeatedThisPhase_NoPrompt
#// The cannot-fire branch: a discard full of units none of which died this phase offers nothing, so the
#// deploy resolves silently rather than raising an empty or fizzle-only prompt.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1Discard: [ASH_242 SEC_080]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:2
P1NODECISION

---

# Deployed_Decline_NothingIsPlayed
#// The printed "You may" on the deployed side. Declining leaves the defeated unit in the discard and
#// costs nothing — the deploy itself is unaffected.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_016
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_128
P1NODECISION

---

# Deployed_SimulateRequestBoundary_StillPlays
#// THE REQUEST-BOUNDARY CELL for the deployed side. Two units die this phase so a real choice is
#// pending for the boundary to interrupt, and the continuation that plays the pick runs in a fresh
#// process.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:1

---

# BothSides_FrontDefeatsAUnit_ThenTheDeployedSideBringsItBack
#// THE CARD'S OWN COMBO, and the only section that exercises both ability sets in one game: the front
#// Action's defeat is what makes a unit "defeated this phase", which is exactly the fuel the deployed
#// side's When Deployed asks for.
#// P1 uses the front Action to play ASH_242 at 3 and immediately defeat it, then deploys Maul (the
#// resources are untouched by the deploy) and replays the very same unit for 5 less — 4 - 5, floored,
#// so it comes back free and this time it STAYS.

## GIVEN
CommonSetup: yyk/bbw/{myResources:11;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: ASH_242

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>DeployLeader
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_242
P1DISCARDCOUNT:0
P1RESAVAILABLE:8

---

# Interaction_TWI208_BothTriggerWindowsResolve_AFTER_TheUnitIsDefeated
#// ── INTERACTIONS: units carrying BOTH a When Played and a When Defeated ──────────────────────────
#// ★ RULING: a leader ability resolves BEFORE the abilities it sets off. Maul's Action plays the unit
#// and defeats it, and only THEN do its triggers resolve — so a unit with both windows has BOTH of them
#// go off after it is already in the discard.
#//
#// TWI_208 Favorable Delegate ("When Played: Draw a card. / When Defeated: Discard a card from your
#// hand.") is the sharpest fixture because its halves move cards in OPPOSITE directions: if either were
#// swallowed by the defeat the deck or the discard would be off by one, rather than netting out.
#// It is Cunning at cost 2, so 1 here. Draw takes deck 3->2; the discard then takes the only card in
#// hand (the one just drawn), so the discard holds TWI_208 itself plus that card.
#//
#// ⚠ KNOWN DIVERGENCE FROM THE RULING, DELIBERATELY ASSERTED AS-IS: the ruling says both triggers go on
#// the stack together and the controller resolves them IN EITHER ORDER. The engine does not offer that
#// choice here — the When Played is flushed from the entry bag and the When Defeated is collected later
#// at the defeat, so they land in SEPARATE batches and always resolve When-Played-first with no prompt.
#// Verified, not assumed: the same board passes identically with and without an EffectStack answer,
#// i.e. any such answer is silently ABSORBED. The order is observable on this very card (drawing first
#// lets you discard the drawn card; discarding first cannot), so a real choice is being denied. Flagged
#// for a decision — batching the two windows is shared trigger plumbing that also affects SEC_018 DJ.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: TWI_208
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DECKCOUNT:2
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1RESAVAILABLE:5
P1NODECISION

---

# Interaction_TWI208_TheDiscardSeesTheDRAWNCard_PinningTheCurrentOrder
#// The order pinned explicitly, so that if the trigger batching is ever changed to honour the ruling
#// this section fails LOUDLY rather than drifting.
#// P1 holds a second card (SOR_128) when Maul's Action resolves. Under the current When-Played-first
#// order the draw happens while SOR_128 is still in hand, giving a two-card hand from which P1 discards
#// the DRAWN card — leaving SOR_128 behind. Under the other order the discard would be forced to take
#// SOR_128 (the only card in hand at that moment) and the hand would end holding the drawn SOR_046.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1Hand: TWI_208
WithP1Hand: SOR_128
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myHand-1

## EXPECT
P1DECKCOUNT:2
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P1DISCARDCOUNT:2
P1NODECISION

---

# Interaction_LAW091_Val_BothShieldsLandOnOppositeSidesOfTheBoard
#// LAW_091 Val ("When Played: Give a Shield token to another friendly unit. / When Defeated: Give a
#// Shield token to an enemy unit.") — two triggers touching OPPOSITE sides, so a swallowed one shows up
#// as a missing shield rather than as a changed count.
#// ⚠ "ANOTHER friendly unit" is the interesting part under Maul: Val is already in the discard when her
#// When Played resolves, so the self-exclusion is moot and the lone friendly SEC_080 takes the shield.
#// Both offers auto-resolve at one legal target each, which is why no answers follow the play.
#// Val is Cunning+Vigilance; her Vigilance pip is uncovered here (+2), so she costs 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_091

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_091
P1RESAVAILABLE:3
P1NODECISION

---

# Interaction_LOF207_LothCat_TheSLASHFormFiresInBothWindows
#// A DIFFERENT SHAPE from the two above, and easy to conflate with them: LOF_207 Loth-Cat prints
#// "When Played/When Defeated: You may exhaust a ground unit" as ONE ability with TWO windows rather
#// than two separate clauses. Under Maul it therefore fires TWICE — once for entering, once for dying —
#// so a player with two ready enemy ground units can exhaust BOTH from a single 1-cost play.
#// Two offers, two answers, and no ordering prompt between them.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LOF_207

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LOF_207
P1NODECISION

---

# Interaction_LOF207_DecliningTheFirstWindowStillLeavesTheSecond
#// The optional half of the slash form. "You MAY exhaust a ground unit" is declinable in EACH window
#// independently, so refusing the first must not consume or cancel the second — a single shared
#// "already used" flag would silently collapse the two windows into one.
#// P1 declines the entry window and takes the defeat window, so exactly ONE unit ends exhausted.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LOF_207

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:1:EXHAUSTED
P1DISCARDCOUNT:1
P1NODECISION

---

# Interaction_ASH167_Flarestar_TwoAdvantageTokensFromTheTwoWindows
#// The slash form again, with a token payload instead of a status change, and from the SPACE arena — so
#// it also shows the findable-marker-then-defeat path is not quietly ground-only.
#// ASH_167 Flarestar Attack Shuttle is Aggression (uncovered here, +2) so it costs 3. Both windows
#// fire, and both tokens are aimed at the same unit to make the count unambiguous.

## GIVEN
CommonSetup: yyk/bbw/{myResources:6;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: ASH_167

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:ASH_167
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:3

---

# Interaction_SOR134_RuthlessRaider_MandatoryDamageResolvesTWICE
#// The slash form once more, but MANDATORY — no "you may" — so neither window can be declined and both
#// must resolve. "Deal 2 damage to an enemy base and 2 damage to an enemy unit", twice over, is 4 to
#// the base and 4 onto the enemy unit.
#// This is the loudest of the five: a single swallowed window reads as 2 instead of 4, and both
#// swallowed reads as 0 — no interpretation needed.
#// SOR_134 is Aggression+Villainy at cost 6; its Aggression pip is uncovered (+2), so it costs 7 here,
#// which is also why this section is the one that needs a double-digit resource pool.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10;myLeader:HMW_016}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_134

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_134
P1RESAVAILABLE:3
P1NODECISION

---

# Front_SecondCopyOfAUnique_DefeatingTheNEWCopy_LeavesTheOldOneStanding
#// ★ THE SECTION THAT ACTUALLY PROVES THE DEFERRAL. Found by mutation: removing the deferral outright
#// left the sibling section above GREEN, because defeating the OLD copy ends with an empty arena under
#// either ordering. Only defeating the NEW copy separates them.
#//
#// With the deferral, Maul's "then defeat it" is still pending when the uniqueness choice resolves, so
#// P1 defeats the just-played copy and Maul's defeat then finds NOTHING (it re-resolves by UniqueID) —
#// exactly one unit dies and the original Gungi is left standing.
#// Without it, Maul defeats the new copy inline first and the uniqueness offer goes stale, so the answer
#// lands on the surviving old copy and BOTH die.
#//
#// The discriminator is therefore the SURVIVOR: ground count 1, with one Gungi still on the board.

## GIVEN
CommonSetup: yyk/bbw/{myResources:12;myLeader:HMW_016}
P1OnlyActions: true
WithP1GroundArena: LOF_093:1:0
WithP1Hand: LOF_093

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_093
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LOF_093
P1NODECISION
