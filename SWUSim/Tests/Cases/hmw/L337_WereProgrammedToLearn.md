# CheapEvent_ReplayedFromDiscardForFree
#// HMW_215 L3-37, We're Programmed to Learn — Cost 6 · 5/7 · Ground · [Cunning][Heroism] ·
#// Underworld, Droid · unique
#// Text: "When you play an event that costs 3 or less: You may play it again from your discard pile
#//        for free. Use this ability only once each phase."
#//
#// COVERAGE: offer=N/A (structural: the ability presents a YESNO — there is no target pool to assert.
#//             The replayed EVENT's own pool belongs to that event, not to L3-37) ·
#//           decline=Decline_NoReplay_AndTheUseIsNotSpent (the decline is also the once-per-phase
#//             consume-on-USE proof) ·
#//           boundary=Boundary_CostThree_Triggers + Boundary_CostFour_DoesNotTrigger (an identically
#//             worded "Deal 3 damage to a unit" event on each side of the threshold) ·
#//           control=N/A (structural: a When-you-play reaction fires for the player who PLAYED the card
#//             and reads only that player's own discard; L3-37 is a legal take-control target, but the
#//             reaction has no seat-scoped zone that could resolve for the wrong player — the observer
#//             loop is GetUnitsInPlay($playingPlayer), so a stolen L3-37 simply observes its NEW
#//             controller's plays, which is the correct reading) ·
#//           reqboundary=RequestBoundary_ReplaySurvivesTheDecision
#//           modes=2P only ("YOU play", "YOUR discard pile" — self-only, no player reference and no
#//             friendly/enemy wording) · TwinSuns=N/A · TeamSuns=N/A
#//
#// FIXTURE: HMW_217 Don't Touch Anything (cost 2, [Cunning][Heroism], "Deal 3 damage to a random enemy
#// unit"). With exactly ONE enemy unit the random pick is deterministic and raises no prompt of its own,
#// so the only decision in the section is L3-37's own YESNO. 3 + 3 = 6 on a 3/7 body that survives both.
#//
#// ⚠ THE DAMAGE TOTAL IS ALSO THE RECURSION GUARD. The replayed copy is itself "an event you played that
#// costs 3 or less", so an implementation that does not spend the once-each-phase use BEFORE replaying
#// would re-trigger on its own replay — 9, 12, or a hang. Exactly 6 is the assertion that pins it.
#// P1RESAVAILABLE:4 pins "for FREE": 6 ready, 2 paid for the first play, nothing for the replay.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: HMW_217
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:6
P1RESAVAILABLE:4
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_217
P1HANDCOUNT:0
P1NODECISION

---

# Decline_EventResolvesExactlyOnce
#// The plain decline: one cheap event, answer NO, and it must resolve ONCE — 3 damage, not 6.
#// ⚠ This section exists because the combined decline-plus-use-not-spent section BELOW does not
#// discriminate on the decline itself: with two events in flight the end state happens to coincide
#// whether or not the first was replayed (a green mutation proved it). Keeping the two properties in
#// separate sections is what makes each one falsifiable.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: HMW_217
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1RESAVAILABLE:4
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_217
P1HANDCOUNT:0
P1NODECISION

---

# Decline_NoReplay_AndTheUseIsNotSpent
#// "You MAY play it again" — declining leaves the event resolved exactly once (3 damage, not 6).
#// It also proves the once-each-phase allowance is consumed ON USE, not on trigger: after the decline a
#// SECOND cheap event in the same phase still raises the offer, and taking it replays that one.
#// Second event deals 3 + 3 = 6 on top of the first 3, so the 3/7 body ends on 9 — dead, hence the
#// assertion is the arena count and the discard rather than a damage number.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: [HMW_217 HMW_217]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_046
P1DISCARDCOUNT:2
P1HANDCOUNT:0
P1NODECISION

---

# OnceEachPhase_ASecondCheapEventDoesNotOffer
#// "Use this ability only once each phase." The first event is replayed; the second cheap event in the
#// same phase must raise NO offer at all. Damage 6 (from the replayed first event) + 3 (the second,
#// played once) = 9 would kill a 3/7, so the second event goes at the BASE instead: the enemy unit is
#// gone from the pool by then. Simpler: assert the second event resolves exactly once and no decision
#// is pending.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: [HMW_217 HMW_217]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:2
P1HANDCOUNT:0
P1NODECISION

---

# AvailableAgainNextPhase
#// THE DURATION HALF of the once-each-phase limit. The round is passed out and the ability offers again.
#// ⚠ Under P1OnlyActions the opponent holds the CLAIMED initiative and LEADS the new round, so the chain
#// needs a trailing P2>Pass. Both players need a seeded deck: the regroup DRAWS, and an empty deck
#// damages the base instead.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: [HMW_217 HMW_217]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1DISCARDCOUNT:2

---

# Boundary_CostThree_Triggers
#// "COSTS 3 OR LESS" — three is inside the threshold. IBH_086 is a cost-3 "Deal 3 damage to a unit",
#// word-for-word the same effect as the cost-4 event in the section below, so the ONLY difference
#// between this pair is the printed cost.
#// The event's own target choice is answered first; the pending decision left afterwards is L3-37's
#// offer, which is what P1HASDECISION pins.
#// ⚠ COST IS THE PRINTED COST. IBH_086 is [Aggression] and this leader/base pair is [Cunning]/[Heroism],
#// so it is billed 3 + 2 off-aspect = 5 resources — and it must STILL count as "costs 3 or less".
#// A gate reading the resources actually paid would refuse it here.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: IBH_086
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HASDECISION
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Boundary_CostFour_DoesNotTrigger
#// Four is outside the threshold: no offer at all. SEC_258 prints the same "Deal 3 damage to a unit"
#// as IBH_086 above and costs one more.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SEC_258
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:3
P1DISCARDCOUNT:1
NOEXTRAACTION

---

# NotAnEvent_ACheapUnitDoesNotTrigger
#// "When you play an EVENT" — a cheap UNIT is not an event. SOR_095 is a vanilla cost-2 unit.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SOR_095
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# OpponentPlaysACheapEvent_DoesNotTrigger
#// "When YOU play an event" — the ACTOR negative, which is a different test from "you don't control
#// L3-37". P2 plays the cheap event; P1 controls L3-37 and must get no offer.
#// P2's copy of HMW_217 hits a RANDOM ENEMY unit — enemy from P2's side, i.e. one of P1's. L3-37 is
#// P1's only unit, so the 3 lands on him deterministically (he is 5/7 and survives).

## GIVEN
CommonSetup: yyw/yyw/{myResources:8;theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: HMW_215:1:0
WithP2Hand: HMW_217

## WHEN
- P2>PlayHand:0

## EXPECT
P1NODECISION
P2NODECISION
P1GROUNDARENAUNIT:0:CARDID:HMW_215
P1GROUNDARENAUNIT:0:DAMAGE:3
P2DISCARDCOUNT:1

---

# BlankedL337_DoesNotTrigger
#// The observer loop skips a unit that has LOST ITS ABILITIES (SEC_046 Galen Erso's naming, and the
#// general LostAbilities gate). SOR_138 Force Lightning blanks L3-37 for the phase; the cheap event then
#// resolves once and offers nothing.
#// SOR_138 is itself an event — and a cost-2 one — so it is played BEFORE L3-37 is blanked would trigger
#// him; it is [Aggression][Villainy] at cost 2, so it triggers L3-37 too. Answer NO to that first offer,
#// then play the cheap event and assert no second offer appears.

## GIVEN
CommonSetup: yyw/rrk/{myResources:10}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: [SOR_138 HMW_217]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
- P1>PlayHand:0

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# ReplayedEventEndsInTheDiscardExactlyOnce
#// The replay MOVES the event out of the discard and back again — it must not leave a duplicate, and the
#// card must not end up in hand or in an arena. One copy in the discard, hand empty.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: HMW_217
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_217
P1HANDCOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_215

---

# TurnPassesExactlyOnce_NoFreeExtraAction
#// A card that PLAYS another card is the classic double-after-action shape: the nested play runs its own
#// finaliser and so does the outer event. One event play is ONE action, so the turn must land on P2 —
#// not swing back to P1. P1OnlyActions is deliberately ABSENT here (it claims initiative for the
#// opponent and makes a double swap indistinguishable from a single one).
#//
#// ⚠ WHY TURNPLAYER AND NOT NOEXTRAACTION. The replay is a nested play of an EVENT, and an event queues
#// its own FINISH_PLAY_CARD terminator, which finalises AFTER the nested frame has already exited. That
#// deferred leg attempts a second close and the action-close gate REFUSES it — the documented benign
#// case, which NOEXTRAACTION reports as an extra close because it counts ATTEMPTS, not landings.
#// Verified rather than assumed: Boundary_CostFour_DoesNotTrigger plays an event with no replay and
#// carries NOEXTRAACTION cleanly, so the one refused attempt is attributable to the replay alone — and
#// here the turn still lands on P2 exactly once with the event resolving twice and only one cost paid.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6;theirResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: HMW_215:1:0
WithP1Hand: HMW_217
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
TURNPLAYER:2
P2GROUNDARENAUNIT:0:DAMAGE:6
P1RESAVAILABLE:4
P1DISCARDCOUNT:1

---

# RequestBoundary_ReplaySurvivesTheDecision
#// The offer is raised in one request and answered in a FRESH PROCESS, so the identity of the event to
#// replay cannot live in an in-memory global — it has to ride the trigger/decision payload.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: HMW_217
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1RESAVAILABLE:4
P1DISCARDCOUNT:1
P1NODECISION

---

# Replay_AggressiveNegotiations_AttacksWithASecondUnit
#// REAL-PLAY SCENARIO 1. SEC_179 Aggressive Negotiations (cost 3, [Aggression]) is "Attack with a unit."
#// Replaying it attacks with a SECOND unit — the first attacker is exhausted by then, so the two
#// resolutions cannot both pick the same body. Two 3-power attackers into an undefended base = 6.
#//
#// P1's hand is EMPTY after the event is played, so Aggressive Negotiations' own "+1/+0 for each card in
#// your hand" rider contributes 0 to both attacks. That is deliberate: it keeps the damage a clean
#// readout of WHICH units attacked rather than a sum of two different bonuses.
#// L3-37 is himself a legal attacker and stays READY — that assertion is what proves the two attacks
#// came from the two intended bodies rather than from him twice.
#// SEC_179 is [Aggression] under a [Cunning]/[Heroism] leader+base, so it is billed 3 + 2 = 5; the
#// replay is free, leaving exactly 1 ready resource.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: [HMW_215:1:0 SOR_095:1:0 SEC_080:1:0]
WithP1Hand: SEC_179

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P2BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:HMW_215
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:2:CARDID:SEC_080
P1GROUNDARENAUNIT:2:EXHAUSTED
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1NODECISION

---

# Replay_PowerOfTheDarkSide_OffAspect_TwinSuns_DefeatsTwoLeaderUnits
#// REAL-PLAY SCENARIO 2, and the richest one — it exercises THREE things at once.
#//
#// (a) OFF-ASPECT COST. SOR_041 Power of the Dark Side is [Vigilance][Villainy] at printed cost 3; under
#//     a [Cunning] base and a [Cunning][Heroism] leader BOTH pips are unmatched, so it is billed
#//     3 + 4 = 7 resources. It must STILL qualify for "costs 3 or less" — L3-37 reads the PRINTED cost,
#//     and this is the sharpest case of that in the file.
#// (b) TWIN SUNS. "AN opponent chooses a unit they control" is a real seat CHOICE at four seats. P2 and
#//     P3 both control units, so two opponents are eligible and P1 must actually name one; P1 names P3
#//     both times. P2's unit standing untouched at the end is what proves the pick was honoured rather
#//     than an OtherPlayer() shortcut landing on seat 2.
#// (c) LEADER UNITS ARE LEGAL. The card says "a unit they control" with no non-leader qualifier, so
#//     P3's two DEPLOYED leaders are the only things they can choose. A defeated leader unit is
#//     defeated and then returns to its leader zone, so P3's arena empties across the two resolutions.
#//
#// ⚠ The second resolution's unit pick still PROMPTS even though P3 is down to one unit by then — the
#// pool is built before the first defeat has compacted out of P3's arena. Measured, not assumed: without
#// the second P3 answer only ONE leader falls and the section reds on P3GROUNDARENACOUNT.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SOR_041
WithP2GroundArena: SOR_095:1:0
WithP3Base: SOR_024
WithP3Leader: SOR_010:1:1
WithP3Leader2: SOR_005:1:1
WithP4Base: SOR_024

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3
- P3>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:0
P3LEADER:NOTDEPLOYED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_215
P1DISCARDCOUNT:1
P1NODECISION

---

# Replay_OpenFire_DealsEightTotalToOneUnit
#// REAL-PLAY SCENARIO 3. SOR_172 Open Fire (cost 3, [Aggression]) is "Deal 4 damage to a unit"; replayed
#// at the same target it totals 8 on one body.
#// The target needs to SURVIVE for the total to be readable, and no vanilla ground unit has 9+ HP — so
#// SOR_046 (3/7) carries SOR_069 Resilient (+0/+3) for an effective 10. DAMAGE:8 with HP:10 is the
#// readout; the unit is alive with 2 remaining.
#// ⚠ HP: is CURRENT MAX HP and does NOT subtract damage, so the survival claim needs BOTH lines plus the
#// arena count — HP:10 alone would read the same on a dead unit's slot.
#// "A unit" is unqualified, so L3-37 is in the pool too and each resolution is a real choice; both are
#// aimed at the same enemy body on purpose.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: HMW_215:1:0
WithP1Hand: SOR_172
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:8
P2GROUNDARENAUNIT:0:HP:10
P1GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:1
P1DISCARDCOUNT:1
P1NODECISION

---

# Combo_ANewAdventure_LadyProxima_LoopOffersTheReplay
#// ⚠⚠ THIS SECTION IS EXPECTED RED — it encodes CR-correct behaviour the engine does not yet implement.
#// Do not "fix" it by weakening the assertion. See the finding note below.
#//
#// THE DOUBLE-YELLOW HAN LOOP (Eternal). Board: L3-37 (HMW_215, cost 6, Underworld) and Lady Proxima
#// (SHD_255, "When you play another Underworld card: You may deal 1 damage to a base") under a
#// [Cunning] base + Han Solo [Cunning][Heroism]. P1 plays A New Adventure (SHD_207, cost 2,
#// [Cunning][Cunning] — on-aspect here, so 2): "Return a non-leader unit that costs 6 or less to its
#// owner's hand. Then, its owner may play it for free."
#//
#// THE CHAIN, per the CR:
#//   1. ANA is PLAYED. L3-37 is in play at that moment, so his "when you play an event that costs 3 or
#//      less" ability TRIGGERS (CR 778.3 — the condition occurring while the card is in play is what
#//      matters).
#//   2. CR 319.6: the event resolves as completely as possible FIRST. ANA returns L3-37 to hand and his
#//      owner replays him for free.
#//   3. That replay is "playing another Underworld card", so LADY PROXIMA triggers and pings a base.
#//   4. CR 778.3 again: L3-37's already-triggered ability MUST still resolve even though he left play
#//      in the meantime — so the offer to replay A New Adventure appears.
#//   5. ANA replays, bouncing L3-37 again. He comes back as a NEW COPY (CR 885), so his once-each-phase
#//      allowance is fresh and the loop continues — pinging the base 1 at a time until the player stops
#//      or the base dies.
#//
#// The assertion is deliberately the MINIMUM that proves the loop can turn: after Proxima's ping, the
#// replay offer is pending. Everything before it already works today (measured live) — the base takes
#// its first ping. Only step 4 is missing.
#//
#// ⚠ WHY IT FAILS TODAY, precisely: SWUCollectOwnPlayReactions intersects the pre-effect observer
#// SNAPSHOT with the units that are in play AT COLLECTION TIME. The original L3-37 is in the snapshot
#// but has left play; the new L3-37 is in play but not in the snapshot. So neither fires and the chain
#// stops after Proxima. Per CR 778.3 the ORIGINAL copy's trigger is the one that must still resolve.
#// This is shared engine behaviour affecting every own-play observer (SOR_182 Bossk, SOR_143, HMW_115,
#// TWI_184, LAW_003, LOF_087 …), not something local to L3-37 — hence a flagged finding rather than a
#// unilateral fix.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: [HMW_215:1:0 SHD_255:1:0]
WithP1Hand: SHD_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1GROUNDARENACOUNT:2
P1HASDECISION

---

# Combo_SecondIteration_TheNewCopyHasAFreshUse
#// THE RULES POINT AT THE HEART OF THE COMBO (CR 885): the L3-37 that comes back is a NEW COPY, so her
#// once-each-phase allowance is FRESH and the loop can turn again. A limit keyed to the PLAYER instead of
#// to the copy — which is how this card was first written — stops the engine dead after one pass, and
#// every other section in this file would still have passed.
#//
#// One full iteration is driven (ANA → bounce L3-37 → replay her free → Proxima pings a base → replay
#// ANA), then a second bounce-and-replay, and the assertion is that the replay offer is pending AGAIN.
#// That second offer can only exist if the new copy brought its own use.
#// The base has taken exactly 1 by this point — Proxima's first ping; her second lands later in the
#// chain, so it is deliberately not asserted here.
#//
#// ⚠ The two triggers (Proxima's ping and L3-37's replay offer) are simultaneous and their order is not
#// fixed between iterations, so this section stops at the point the ordering is still deterministic
#// rather than driving a long brittle answer chain.

## GIVEN
CommonSetup: yyw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP1GroundArena: [HMW_215:1:0 SHD_255:1:0]
WithP1Hand: SHD_207

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:1
P1HASDECISION
P1DECISIONTOOLTIP:Play_A_New_Adventure_again_for_free?
