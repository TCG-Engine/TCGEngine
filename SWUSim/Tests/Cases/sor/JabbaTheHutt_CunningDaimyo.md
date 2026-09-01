# TrickEvent_CostReduced
#// SOR_181 Jabba the Hutt — passive: "Each TRICK event you play costs 1 less." With Jabba in play, P1
#// plays SOR_222 (Return a non-leader unit to hand — a Trick event, cost 3 Cunning) for 2 (3 ready
#// resources → 1 left). Two non-leader units are in play (Jabba + enemy SOR_128); P1 bounces the enemy.
#// COVERAGE: offer=WhenPlayed_NonTrickPickIsREFUSEDServerSide — a top-deck SEARCH is answered by
#//           CardID, not by an arena mzID, so P1SELECTABLEEXACT reads an empty set on it; the pool is
#//           instead asserted by proving an out-of-filter pick is REJECTED server-side (with
#//           WhenPlayed_SearchTrickDraw as the passing control that the legal pick IS accepted) ·
#//           reqboundary=WhenPlayed_TrickIsTheEIGHTHCard_StillFound and every other search section —
#//           the peeked cards stay in the deck and only the finalize mutates it, so the CardID answer
#//           carries no in-memory state; the decline sections additionally prove the peeked cards are
#//           restored rather than destroyed, which is the failure mode a lost continuation produces ·
#//           control=ControlTakenJabba_TheDiscountFollowsTheCONTROLLER (owner differs from controller:
#//           the passive resolves for the CONTROLLER, and the bounced Jabba lands in the OWNER's hand)
#//           + ControlTakenJabba_ItsOWNERNoLongerGetsTheDiscount · boundary=WhenPlayed_TrickIsThe
#//           EIGHTHCard_StillFound vs WhenPlayed_TrickIsTheNINTHCard_NotFound (the N vs N+1 pair on
#//           "top 8"), plus this section (Waylay for 2) vs TrickDiscount_NonTrickEventIsFullPrice (an
#//           identically-costed non-Trick Cunning event for 3) as the -1 discount pair ·
#//           decline=WhenPlayed_NoTrickEventInTopEight_NothingDrawn + WhenPlayed_TrickIsTheNINTHCard_
#//           NotFound — the search is presented even when nothing matches and is declined with '-'.
#//           The DISCOUNT clause has no decline branch: it is a passive cost modifier with no "you
#//           may", applied by SWUComputePlayCost without asking.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_222

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# WhenPlayed_SearchTrickDraw
#// SOR_181 Jabba the Hutt (Unit 2/8, cost 4, Cunning/Villainy) — "When Played: Search the top 8 cards
#// of your deck for a TRICK event, reveal it, and draw it." Deck holds a non-Trick event (SOR_171), a
#// non-Trick unit (SOR_095), and one Trick event (SOR_222). Only the Trick event is offered (filter is
#// Trick trait + event) → drawn. The other two go to the bottom (deck 3 → 2).

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: SOR_171
WithP1Deck: SOR_095
WithP1Deck: SOR_222
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_222

## EXPECT
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_222
P1DECKCOUNT:2

---

# TrickDiscount_NonTrickEventIsFullPrice
#// SOR_181 Jabba the Hutt — "Each TRICK event you play costs 1 less." The load-bearing negative for
#// TrickEvent_CostReduced: SOR_221 Outmaneuver is the same cost (3), the same aspect (Cunning) and the
#// same card type (Event) as SOR_222 Waylay — it differs ONLY in its trait (Tactic, not Trick). With
#// Jabba in play it must cost the full 3, so 3 ready resources go to 0.
#// Paired against TrickEvent_CostReduced (Waylay for 2) this is the one comparison that isolates the
#// TRAIT as the thing being tested; a modifier that discounted every Cunning event, or every event,
#// passes the positive section and fails only here. The arena answer is "Space" so the exhaust lands
#// in an empty arena and leaves Jabba alone.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP1Hand: SOR_221

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Space

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY

---

# TrickDiscount_OpponentsTrickEventIsFullPrice
#// SOR_181 Jabba the Hutt — "Each Trick event YOU play costs 1 less": the discount is scoped to Jabba's
#// controller, so an OPPONENT playing a Trick event pays full price. P1 holds Jabba; P2 plays Waylay
#// (Trick, cost 3) with exactly 3 resources and ends on 0 ready — no discount. If the modifier ignored
#// the "you" it would leave P2 with a resource and nothing else in this file would notice, because every
#// other section plays the Trick from Jabba's own side.
#// P2 bounces their OWN Death Star Stormtrooper (Waylay is unqualified — both non-leader units are
#// legal, so the pick stays interactive) which keeps Jabba on the board through the assertion.

## GIVEN
CommonSetup: yyk/yyk/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_181:1:0
WithP2GroundArena: SOR_128:1:0
WithP2Hand: SOR_222

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2RESAVAILABLE:0
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# ControlTakenJabba_TheDiscountFollowsTheCONTROLLER
#// SOR_181 Jabba the Hutt × a control change. P1 CONTROLS a Jabba that P2 OWNS. The passive is read off
#// the source object's CONTROLLER, not its owner, so it is now P1's Trick events that cost 1 less:
#// Waylay for 2 out of 3 resources, 1 left.
#// The same section also pins the owner half of the bounce: Jabba is the only non-leader unit in play,
#// so the pick auto-resolves onto him and "return it to ITS OWNER'S hand" must send him to P2's hand,
#// not to the hand of the player who controlled him. Both readings of a control change — who resolves
#// it, and whose zone it lands in — are asserted in one board.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArenaControlled: SOR_181:2
WithP1Hand: SOR_222

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P2HANDCOUNT:1

---

# ControlTakenJabba_ItsOWNERNoLongerGetsTheDiscount
#// SOR_181 Jabba the Hutt × a control change, the other side of the same coin. P1 controls the
#// P2-OWNED Jabba, and now P2 — his owner — plays a Trick event: it costs the FULL 3, because the
#// passive belongs to whoever controls Jabba, not to whoever owns him.
#// Without this, ControlTakenJabba_TheDiscountFollowsTheCONTROLLER is satisfied by an implementation
#// that hands the discount to BOTH seats (e.g. one that compares against Owner and Controller in turn,
#// or that dropped the seat comparison altogether).

## GIVEN
CommonSetup: yyk/yyk/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArenaControlled: SOR_181:2
WithP2GroundArena: SOR_128:1:0
WithP2Hand: SOR_222

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2RESAVAILABLE:0
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P1GROUNDARENACOUNT:1

---

# WhenPlayed_NoTrickEventInTopEight_NothingDrawn
#// SOR_181 Jabba the Hutt — "Search the top 8 cards of your deck FOR A TRICK EVENT": the whiff branch.
#// The deck holds a non-Trick event (SOR_171 Mission Briefing, trait Plan), a unit (SOR_095) and
#// another unit (SOR_046) — nothing the filter accepts. The search is still presented (the player is
#// entitled to look), and declining it draws nothing: hand stays empty and all three cards go back,
#// deck 3 -> 3. Note the peeked cards are held by the pending decision, so leaving it unanswered would
#// read as deck 0 — the decline is what puts them back, and that restoration is the assertion.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: SOR_171
WithP1Deck: SOR_095
WithP1Deck: SOR_046
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1GROUNDARENACOUNT:1
P1NODECISION

---

# WhenPlayed_NonTrickPickIsREFUSEDServerSide
#// SOR_181 Jabba the Hutt — the pool assertion for a top-deck SEARCH. A card search is answered by
#// CardID rather than by an arena mzID, so its legal set cannot be read off a pending target-choice;
#// what CAN be asserted is that an out-of-filter answer is REJECTED by the server rather than acted on.
#// The peeked three are SOR_171 Mission Briefing (an Event, but trait Plan), SOR_222 Waylay (the one
#// legal pick) and SOR_146 Zeb Orrelios (a unit). P1 answers SOR_171 — the non-Trick event, i.e. the
#// answer that a filter checking only "is an Event" would accept.
#// Intended: the pick is discarded, nothing is drawn, and all three cards go back to the deck (3 -> 3).
#// This is the discrimination that a green WhenPlayed_SearchTrickDraw cannot make on its own: the UI
#// constrains the click, so only a server-side re-check keeps the filter honest.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: SOR_171
WithP1Deck: SOR_222
WithP1Deck: SOR_146
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_171

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:3
P1GROUNDARENACOUNT:1
P1NODECISION

---

# WhenPlayed_TrickIsTheEIGHTHCard_StillFound
#// SOR_181 Jabba the Hutt — "the TOP 8 cards": the inclusive edge. Waylay sits at deck position 8, the
#// last card the search may see, with a ninth card (SOR_171) behind it. It is found and drawn: hand 1,
#// deck 9 -> 8. Pairs with WhenPlayed_TrickIsTheNINTHCard_NotFound below.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 SOR_237 SOR_225 SOR_229 SOR_067 SOR_222 SOR_171]
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_222

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_222
P1DECKCOUNT:8

---

# WhenPlayed_TrickIsTheNINTHCard_NotFound
#// SOR_181 Jabba the Hutt — the N vs N+1 boundary on the search depth. Same nine-card deck as the
#// section above with the last two swapped, so the ONLY Trick event sits at position 9 — one past the
#// window. The search sees eight non-Trick cards, finds nothing, and the decline puts all nine back:
#// hand 0, deck 9. A search wired to the whole deck (or to 9, or to "top 8 or more") draws Waylay here
#// and passes every other section in this file.

## GIVEN
CommonSetup: yyk/yyk/{myResources:4}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_046 SOR_128 SOR_237 SOR_225 SOR_229 SOR_067 SOR_171 SOR_222]
WithP1Hand: SOR_181

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:9
P1GROUNDARENACOUNT:1
P1NODECISION
