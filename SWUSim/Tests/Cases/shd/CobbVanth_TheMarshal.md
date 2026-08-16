# WhenDefeated_NoCheapUnit_NoDiscard
#// SHD_115 — the search finds no unit costing 2 or less (deck top is only a Wampa, cost 4). The search
#// resolves with no valid pick (PASS): nothing is discarded from the deck. Only SHD_115 itself sits in
#// the discard, and it is not free-playable.
#// COVERAGE: offer=AnotherPlayFromDiscardEffect_OffersTheTaggedCardExactlyOnce (SELECTABLEEXACT over the
#//           discard pool, left PENDING) + SearchFilter_IneligiblePickIsRejected (the top-10 search is a
#//           card-ID display, not an mzID target choice, so SELECTABLE cannot address it — the legal set
#//           is asserted by its EFFECT: an ineligible answer discards nothing) · reqboundary=
#//           FreePlayPermissionExpiresNextPhase (the stamp is re-read after a regroup crossing) +
#//           PlayedFromDiscardThenDefeated_NotPlayableFromDiscardAgain (re-read after two turn swaps) ·
#//           control=UnderEnemyControl_TheControllerSearchesAndPlaysFree (the CONTROLLER searches its own
#//           deck and gets the free play; the owner only gets Cobb's corpse) · boundary pair=
#//           WhenDefeated_SearchDiscardFreePlay (this phase -> free) vs FreePlayPermissionExpiresNextPhase
#//           (next phase -> no-op), and ReturnedToHand_NotFreeAndUnaffordable vs
#//           ReturnedToHand_PlayableAtFullCost (in hand -> full price, unaffordable vs affordable) ·
#//           decline=WhenDefeated_NoCheapUnit_NoDiscard (nothing eligible -> take nothing) +
#//           SearchFilter_IneligiblePickIsRejected (an out-of-filter pick degrades to take nothing)

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_164
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P1DECKCOUNT:3

---

# WhenDefeated_SearchDiscardFreePlay
#// SHD_115 (3-cost 3/2 Command) — "When Defeated: Search the top 10 cards of your deck for a unit that
#// costs 2 or less and discard it. For this phase, you may play that card from your discard pile for
#// free." P1's SHD_115 attacks a Wampa (SOR_164, 4/5): deals 3 (Wampa survives), counters 4 → SHD_115
#// (2 HP) dies. Its When Defeated searches → the ≤2 unit SOR_095 (cost 2) is discarded tagged free. P1
#// then plays SOR_095 from the discard for FREE (5 resources untouched).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:5
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115

---

# SearchFilter_IneligiblePickIsRejected
#// SHD_115 — "a unit that costs 2 or less" is a server-enforced filter, not a client hint. The top-3 mix is
#// deliberately discriminating: SOR_046 (Unit, cost 4 — too expensive), SOR_171 (an EVENT, cost 3 — not a
#// unit) and SOR_095 (Unit, cost 2 — the only legal pick). P1 answers the search with the INELIGIBLE
#// SOR_046: it is dropped, nothing is discarded from the deck, and the peeked cards go back to the bottom
#// (deck stays at 3). Only SHD_115 itself is in the discard, so there is nothing carrying the free-play
#// permission. Intended: an ineligible answer must behave exactly like "take nothing".

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_171
WithP1Deck: SOR_095
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_046

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P1DECKCOUNT:3

---

# OpponentCannotPlayTheFreeCardFromP1Discard
#// SHD_115 — "you may play that card from YOUR discard pile for free" is a permission for the discard's
#// owner only. After Cobb dies and tags SOR_095 in P1's discard, P2 (with 5 ready resources) attempts to
#// play it out of P1's discard: the attempt is a silent no-op — SOR_095 stays in P1's discard, P2's board
#// is unchanged (only the Wampa) and P2's resources are untouched.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;theirResources:5}
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P2>PlayFromOpponentDiscard:1

## EXPECT
P1DISCARDCOUNT:2
P1DISCARDUNIT:1:CARDID:SOR_095
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2RESAVAILABLE:5

---

# PilotUnitFromDiscard_EntersAsAUnit
#// SHD_115 — the free-play permission is on the CARD, so a discarded Piloting unit may be played as a
#// unit even while a friendly Vehicle (SOR_237, no Pilot) sits in space. JTL_196 Dagger Squadron Pilot
#// (cost 1, Piloting) is the only <=2 unit in the top 3; P1 plays it from the discard for free, CHOOSES
#// the Unit mode at the Unit-vs-Pilot prompt, and it enters the GROUND arena as a unit — the X-Wing is
#// left bare, and no resources are spent.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: JTL_196
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:JTL_196
- P1>PlayFromDiscard:1
- P1>AnswerDecision:Unit

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_196
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:5
P1DISCARDCOUNT:1



---

# FreePlayPermissionExpiresNextPhase
#// SHD_115 — "For THIS PHASE, you may play that card from your discard pile for free." Cobb dies this
#// action phase and tags SOR_095 in P1's discard; both players then pass through regroup into the NEXT
#// action phase. P1's play attempt is now a silent no-op: SOR_095 stays in the discard. The trailing
#// P1>PlayHand is the positive control that P1 really is the active player in the new phase (a drawn
#// SOR_128, cost 1 +2 off-aspect = 3, drops P1 from 5 ready resources to 2) — without it a no-op play
#// and a no-op turn look identical. Boundary partner of WhenDefeated_SearchDiscardFreePlay (same phase
#// -> the free play succeeds).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;theirResources:5}
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP1Deck: SOR_128
WithP2GroundArena: SOR_164:1:0
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayFromDiscard:1
- P1>PlayHand:0

## EXPECT
PHASE:MAIN
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
P1DISCARDUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:2

---

# AnotherPlayFromDiscardEffect_OffersTheTaggedCardExactlyOnce
#// SHD_115 — a second play-from-discard effect must see the tagged card ONCE, not twice (the free-play
#// permission is a property of the discard entry, not an extra copy of the card). Cobb dies and tags
#// SOR_095; P1 then plays SHD_094 Palpatine's Return ("Play a unit from your discard pile") and its
#// target offer is exactly the two units in P1's discard — myDiscard-0 (SHD_115) and myDiscard-1
#// (SOR_095) — with no duplicate entry for the tagged SOR_095. The choice is left PENDING so the OFFER
#// itself is what is asserted.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_094
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-1
P1GROUNDARENACOUNT:0

---

# AnotherPlayFromDiscardEffect_ResolvesOnTheTaggedCard
#// Resolution half of the section above: SHD_094 Palpatine's Return ("It costs 6 resources less") is
#// resolved onto the SHD_115-tagged SOR_095. The marine (cost 2 - 6, floored at 0) enters P1's ground
#// arena and only the event's own 6 resources are spent, so P1 ends on 0 ready resources. The tagged
#// entry is consumed by whichever effect plays it — the discard is left holding SHD_115 and the spent
#// SHD_094 only.

## GIVEN
CommonSetup: ggk/ggk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_094
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0
P1DISCARDCOUNT:2

---

# PlayedFromDiscardThenDefeated_NotPlayableFromDiscardAgain
#// SHD_115 — the permission is spent when the card is played. P1 plays the tagged SOR_095 out of the
#// discard for free; P2's Wampa (4/5) then kills it (3/3) and it returns to P1's discard as a FRESH,
#// untagged entry. P1's second play attempt in the SAME phase is a silent no-op. The trailing
#// P1>PlayHand (SOR_128, cost 1 +2 off-aspect = 3) is the positive control that P1 still had a real
#// action available, so the no-op cannot be confused with a lost turn.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;theirResources:5}
WithP1Hand: SOR_128
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P2>Pass
- P1>PlayFromDiscard:1
- P2>AttackGroundArena:0:0
- P1>PlayFromDiscard:1
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:2
P1DISCARDUNIT:1:CARDID:SOR_095
P1RESAVAILABLE:2

---

# ReturnedToHand_NotFreeAndUnaffordable
#// SHD_115 — the free-play permission belongs to the DISCARD ENTRY, not to the card. P1's SEC_105
#// Renewed Friendship (cost 4) returns the tagged SOR_095 from the discard to P1's hand; with only 1
#// ready resource left, playing the 2-cost marine FROM HAND is a silent no-op — it is not free there.
#// (The 2 ground units are SEC_105's Spy tokens.) Boundary partner of
#// ReturnedToHand_PlayableAtFullCost, which pays the full 2 and succeeds.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SEC_105
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01

---

# ReturnedToHand_PlayableAtFullCost
#// SHD_115 — losing the free-play permission does not make the card unplayable, only unfree. Same setup
#// as ReturnedToHand_NotFreeAndUnaffordable but with 6 starting resources: after SEC_105 (4) P1 has 2
#// ready, pays the marine's full cost of 2 from hand and it enters the ground arena alongside the two
#// Spy tokens, leaving 0 ready resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SEC_105
WithP1GroundArena: SHD_115:1:0
WithP1Deck: SOR_095
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-1
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_095




---

# UnderEnemyControl_TheControllerSearchesAndPlaysFree
#// SHD_115 — the When Defeated resolves for the unit's CONTROLLER, not its owner. P2's JTL_043 No Glory,
#// Only Results takes control of P1's Cobb Vanth and defeats it, so P2 searches P2's OWN deck, discards
#// SOR_095 into P2's discard and may play it from there for free. Cobb itself still goes to its OWNER
#// P1's discard. P2's own Wampa is on the board purely so No Glory has two legal targets and really
#// prompts (with one target it would auto-resolve and swallow the answer). P2 spends only the event's 5
#// resources; the marine is free from the discard even though Command/Heroism is off-aspect for P2.

## GIVEN
CommonSetup: ggk/bbk/{theirResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SHD_115:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Hand: JTL_043
WithP2Deck: SOR_095
WithP2Deck: SOR_171
WithP2Deck: SOR_171

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:SOR_095
- P2>PlayFromDiscard:1

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_115
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2RESAVAILABLE:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:JTL_043

---

# PilotUnitFromDiscard_AsPilotUpgradeOnFriendlyVehicle
#// SHD_115 — "you may play THAT CARD from your discard pile for free" covers a Piloting unit's UPGRADE
#// mode too (ruling: because the ability says "play that card", a Pilot unit may be played as a unit OR
#// as an upgrade). JTL_196 Dagger Squadron Pilot is played out of the discard as a Pilot upgrade on the
#// friendly Vehicle SOR_237, which has no Pilot. It is FREE: "play for free" ignores all resource costs
#// including the aspect penalty, and that covers the PILOTING cost too — JTL_196's Piloting cost is
#// [1 resource] and it is off-aspect here (Cunning/Heroism under a Command board), yet all 5 resources
#// are still available afterwards. Boundary partner of PilotUnitFromDiscard_EntersAsAUnit: same board,
#// same permission, the other mode.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_115:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: JTL_196
WithP1Deck: SOR_171
WithP1Deck: SOR_171
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:JTL_196
- P1>PlayFromDiscard:1
- P1>AnswerDecision:Pilot

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_196
P1RESAVAILABLE:5
P1DISCARDCOUNT:1
