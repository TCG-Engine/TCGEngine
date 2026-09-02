# ChooseTwo_CostDropsByTwo_EachChosenTakesOne
#// COVERAGE: offer=Offer_FriendlyOnly_IncludesAStolenUnit_ExcludesEnemies
#//           decline=ChooseNone_DashDecline_FullPrice AND ChooseNone_EmptyConfirm_FullPrice
#//                   (⚠ `-` and PASS are two DIFFERENT declines — see those sections)
#//           boundary=Glow_AffordableOnlyWithTheReduction / Glow_UnaffordableEvenAtMaxReduction
#//                    (the N vs N-1 pair on the reduction) + OverChoose_CostFloorsAtZero
#//           control=Offer_FriendlyOnly_IncludesAStolenUnit_ExcludesEnemies ("friendly" is CONTROL, so a
#//                   unit stolen from the opponent is a legal pick and its owner cannot pick it)
#//           reqboundary=RequestBoundary_PicksSurviveIntoTheCostStep
#//           modes=2P,TeamSuns ("FRIENDLY units" spans the TEAM — TeamSuns_TeammateUnitIsFriendly) ·
#//                 TwinSuns=N/A (no player reference at all: the pool is friendly-scoped and the
#//                 discount is the caster's, so nothing fans out across opponents)
#//
#// HMW_125 The Marauder, A New Home (7 cost, 5/7 SPACE, Command/Heroism, Vehicle/Transport)
#//   "While playing this unit, you may choose any number of friendly units. Deal 1 damage to each of
#//    them. For each unit chosen this way, this unit costs 1 resource less."
#//
#// The baseline: 7 resources, two friendly units chosen → cost 7-2 = 5 paid, 2 resources left ready,
#// and both chosen units carry 1 damage. The Marauder lands in the SPACE arena.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:1
P1RESAVAILABLE:2

---

# ChooseNone_DashDecline_FullPrice
#// "Any number" includes NONE. Declining with `-` pays the full 7 and damages nothing.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1RESAVAILABLE:0

---

# ChooseNone_EmptyConfirm_FullPrice
#// ⚠⚠ THE OTHER DECLINE, and the one that historically breaks. `-` and PASS are DIFFERENT answers:
#// confirming a multi-select with nothing selected submits the literal "PASS", which goes STICKY and
#// makes ExecuteStaticMethods skip every following CUSTOM that is not flagged dontSkipOnPass. For a
#// picker whose continuation is what PLAYS THE CARD, that skip means the card is never played and simply
#// VANISHES — measured on Exploit/TWI_167 on 2026-08-27, where the `-` decline test passed the whole
#// time. Byte-for-byte twin of the section above so the two can never silently diverge.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Offer_FriendlyOnly_IncludesAStolenUnit_ExcludesEnemies
#// ⚠ THE OFFER CELL, doubling as the control-change cell. "FRIENDLY units" is about CONTROL: P1 first
#// steals P2's SOR_046 with Change of Heart (SOR_224), so that unit is friendly to P1 and must appear in
#// the pool, while P2's remaining unit must not. The Marauder itself is still in HAND at this point, so
#// it is not in the pool either.
#// ⚠ A stolen unit sorts AFTER every plain WithP1GroundArena unit, so P1's board is
#// [SOR_095 (own), SOR_046 (stolen)].

## GIVEN
CommonSetup: ggw/grk/{myResources:14}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: SOR_224
WithP1Hand: HMW_125

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Glow_AffordableOnlyWithTheReduction
#// ⚠ THE AFFORDABILITY GATE. The Marauder costs 7 and P1 holds 5 ready resources, so on printed cost it
#// is unaffordable — but two friendly units make it a 5, and it IS playable. A glow computed from the
#// printed cost leaves the card DARK BUT CLICKABLE, which is exactly the reported
#// "affordable cards in hand aren't highlighted" shape.
#// The play itself is asserted too, so this pins the gate AND the payment agreeing with each other.

## GIVEN
CommonSetup: ggw/grk/{myResources:5;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Drain

## EXPECT
P1HANDGLOW:0

---

# Glow_UnaffordableEvenAtMaxReduction
#// ⚠ THE BOUNDARY PARTNER. Same 5 resources, but only ONE friendly unit — the best possible price is
#// 7-1 = 6, still more than 5, so the card must NOT glow. Without this pair the section above passes for
#// any implementation that simply always lights the card up.

## GIVEN
CommonSetup: ggw/grk/{myResources:5;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>Drain

## EXPECT
P1HANDGLOWNOT:0

---

# ShieldedPick_DamagePrevented_ButStillCountsForTheDiscount
#// ⚠ "For each unit CHOSEN this way" — not "damaged", and not "defeated" (contrast Exploit, whose
#// reminder text says "for each unit DEFEATED" and whose handler deliberately counts only successful
#// defeats). A Shield eats the 1 damage and the discount is unchanged: 7-2 = 5 paid either way.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:DAMAGE:1
P1RESAVAILABLE:2

---

# ChosenOneHpUnitIsDefeated_AndStillCountsForTheDiscount
#// The damage is real and can be lethal — LAW_180 is a 3/1, so its own 1 kills it. It was still CHOSEN,
#// so the discount holds: 7-2 = 5 paid, 2 ready left, and the dead unit is in P1's own discard.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: LAW_180:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_180
P1RESAVAILABLE:2

---

# OverChoose_CostFloorsAtZero
#// "ANY NUMBER" is literal — there is no cap at the printed cost, and over-choosing is a legal (if
#// wasteful) line. Seven friendly units chosen against a cost of 7 makes the play FREE: all 7 ready
#// resources are still ready afterwards, and all seven units carry 1 damage.
#// ⚠ This is also what proves the offer's max is the POOL SIZE and not some invented ceiling.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2&myGroundArena-3&myGroundArena-4&myGroundArena-5&myGroundArena-6

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENACOUNT:7
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:6:DAMAGE:1
P1RESAVAILABLE:7

---

# NoFriendlyUnits_NoPrompt_FullPrice
#// With an empty friendly board there is nothing to choose, so no picker is raised at all and the card
#// is simply played for 7. A picker offered over an empty pool would be a prompt with no answer.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0
P1NODECISION

---

# TeamSuns_TeammateUnitIsFriendly
#// ⚠ "FRIENDLY units" spans the TEAM — a teammate's unit is friendly even though you do not control it,
#// so seat 3 (seat 1's partner, since teams are seat parity) must be in the pool and be damageable, and
#// picking it must reduce the cost like any other. Seat 2's unit is an enemy and must be absent.
#// This is the only place friendly-vs-controlled differ, so it is the only multiplayer section here.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
WithTeams: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&p3GroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:2

---

# RequestBoundary_PicksSurviveIntoTheCostStep
#// ⚠ THE REQUEST-BOUNDARY CELL, and this card is the one that most needs it: the picker is answered in a
#// LATER request, and everything the cost step needs — which hand card is being played, the running
#// discount, and the caller's consume-once play-grant globals — has to ride the CUSTOM's own Param.
#// An in-memory carry would be empty here and the play would either pay full price or lose the card
#// entirely (the JTL_094 family). Byte-identical to the opening section apart from the boundary.

## GIVEN
CommonSetup: ggw/grk/{myResources:7;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P1RESAVAILABLE:2

---

# UnderChoose_StillUnaffordable_NothingHappens
#// ⚠ THE HALF-APPLIED PLAY. Reported 2026-08-28. Same fixture as Glow_AffordableOnlyWithTheReduction —
#// 5 resources, cost 7, two friendly units — so the card legitimately GLOWS: it is playable at the full
#// 2-unit reduction. But the player may confirm just ONE pick, which prices it at 6 and the payment then
#// fails ("Not enough ready resources").
#// Playing a card is ATOMIC: a play that cannot be paid for did not happen, so the 1 damage the pick
#// would have taken must not be on the board either. Every assertion here is a piece of "nothing
#// happened" — no damage on either unit, the Marauder still in hand, and all 5 resources still ready.

## GIVEN
CommonSetup: ggw/grk/{myResources:5;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:5

---

# ChooseEnough_ExactlyAffordable_MarauderLands
#// ⚠ THE BOUNDARY PARTNER for UnderChoose_StillUnaffordable_NothingHappens. Identical fixture — 5
#// resources, cost 7, two friendly units — but BOTH picks are confirmed, so the price is exactly 5 and
#// the play goes through: each chosen unit takes its 1 damage and the Marauder reaches the space arena
#// with 0 resources left. Without this the gate above passes for an implementation that refuses every
#// reduced play.

## GIVEN
CommonSetup: ggw/grk/{myResources:5;handCardIds:HMW_125}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:HMW_125
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# Ruling_TheInFlightMarauderIsNOTInPlay_AndIsNotInItsOwnPool
#// JUDGE Q&A (2026-09-01): "Can the Marauder hit itself to reduce the cost by 1?" NO. "While playing
#// this unit" places the whole choose-and-damage in the DETERMINE COSTS / PAY COSTS step, at which
#// point the card is still in HAND and is not a unit in play - so it is not one of the "any number of
#// friendly units" it may choose.
#//
#// The pool gets this right structurally: it is built from SWUAllUnits, which walks ARENAS only, so a
#// card in hand can never appear in it. But that is a property of a shared helper rather than a guarded
#// behaviour of this card, and the offer section above excludes the in-flight copy only INCIDENTALLY -
#// its subject is friendly-vs-enemy. This section exists to state the ruling and fail if it changes.
#//
#// ⚠ THE STRONGER FORM IS NO LONGER WRITEABLE, and that is a good thing. Submitting the Marauder's own
#// hand slot as an answer is now REFUSED engine-wide by SWUValidateDecisionAnswer before the handler
#// ever sees it ("'myHand-0' is not a candidate ... [0|2|myGroundArena-0&myGroundArena-1]"), and a
#// refusal cannot be expressed as an assertion. So the ruling is pinned POSITIVELY instead: two
#// friendly units in play and TWO cards in hand - the Marauder plus a second card, so that both hand
#// slots exist and a pool that leaked hand entries would have somewhere visible to leak to.
#// The offered pool must be exactly the two arena units.
## GIVEN
CommonSetup: ggw/grk/{myResources:7}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_125
WithP1Hand: SOR_251
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
P1HANDCOUNT:2

---

# Ruling_ADIFFERENTCopyALREADYInPlayISSelectable
#// THE OTHER HALF OF THE RULING, and the section that pins WHY the in-flight copy is excluded.
#// It is excluded because it is not in play - NOT because of what card it is. A different copy of The
#// Marauder that is already on the board is an ordinary friendly unit and is a perfectly legal pick.
#// This is the discriminating case for the obvious wrong implementation: filtering the pool by CardID
#// ("never offer HMW_125") looks identical on every other section in this file and fails only here.
#// The decision is left PENDING and the pool asserted, so nothing depends on how the uniqueness rule
#// resolves the two copies afterwards.
#// P1's only friendly unit is the in-play Marauder, so the offered pool is exactly it.
## GIVEN
CommonSetup: ggw/grk/{myResources:14;handCardIds:HMW_125}
P1OnlyActions: true
WithP1SpaceArena: HMW_125:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0
