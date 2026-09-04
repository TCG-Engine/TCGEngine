# WhenDefeated_FewerResources_ResourcesHandCardThenTopOfDeck
#// HMW_044 Ima-Gun Di - Holding Out For Resupply — Cost 3 · 5/3 · Ground · [Command][Aggression][Heroism]
#// · Force, Jedi, Republic · unique
#// Text: "When Defeated: If you control fewer resources than an opponent, you may resource a card from
#//        your hand. If you do, resource the top card of your deck."
#//
#// COVERAGE: offer=Offer_ExactlyTheHandAndNothingElse (SELECTABLEEXACT over all three hand cards)
#//           decline=Decline_NoHandResourceAndNoTopDeck · cannotpay=EmptyHand_NoPromptAtAll (a decline
#//             and an empty hand are DIFFERENT branches and both must skip the rider)
#//           boundary=Boundary_EqualResources_DoesNotFire + Boundary_OneFewer_Fires ("fewer" is STRICT)
#//           control=ControlChange_NewControllerResolvesItAgainstTheirOwnZones (JTL_043 takes control
#//             then defeats — all three "you"/"your" readings move to the new controller)
#//           reqboundary=RequestBoundary_RiderStillFires
#//           modes=2P,TwinSuns ("an opponent" is a player reference — but an EXISTENTIAL CONDITION, not
#//             a prompt; see TwinSuns_FewerThanAFarSeatOnly_Fires) · TeamSuns=N/A (no friendly/enemy
#//             wording; OpponentsOf already excludes a teammate, which is the correct reading either way)
#//
#// DEATH BY ATTACKER SELF-DEFEAT. A DEFENDER killed in cross-player combat leaves its When Defeated
#// pending on the non-active player's queue; attacking into a bigger body resolves it inline in P1's
#// own action. Ima-Gun Di is 5/3 into SOR_046's 3/7: he deals 5 (it lives on 7), it counters 3 (he dies).
#//
#// P1 controls 4 resources, P2 controls 7 — so the condition holds. Both resourced cards enter
#// EXHAUSTED (no "and ready it" rider), which is what P1RESAVAILABLE:4 pins: the count goes 4 -> 6 while
#// the READY count does not move.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_044
P1RESCOUNT:6
P1RESAVAILABLE:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1NODECISION

---

# WhenDefeated_MoreResourcesThanTheOpponent_NoPrompt
#// THE LOAD-BEARING NEGATIVE. P1 controls 7, P2 controls 4 — P1 does NOT control fewer, so the whole
#// ability is skipped: no prompt, no hand card resourced, no top-deck resource. A handler that ran the
#// clause unconditionally reds on every one of these.

## GIVEN
CommonSetup: grw/rrk/{myResources:7}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 4:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P1NODECISION

---

# Boundary_EqualResources_DoesNotFire
#// "FEWER" IS STRICT. Five against five is not fewer, so nothing happens. This is the low half of the
#// boundary pair — on its own it proves nothing, which is why Boundary_OneFewer_Fires sits next to it
#// on an otherwise identical board.

## GIVEN
CommonSetup: grw/rrk/{myResources:5}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 5:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1RESCOUNT:5
P1HANDCOUNT:2
P1DECKCOUNT:2
P1NODECISION

---

# Boundary_OneFewer_Fires
#// The high half of the pair: exactly one fewer than the opponent IS fewer. Identical to the section
#// above except P1 controls 4 instead of 5.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 5:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:6
P1HANDCOUNT:1
P1DECKCOUNT:1

---

# Decline_NoHandResourceAndNoTopDeck
#// "You may" — declined with a legal card in hand. The rider is joined by "IF YOU DO", so declining
#// must take the top-deck resource down with it. Asserting the DECK is the half that matters: a handler
#// that resources the top card unconditionally passes every other section in this file.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1RESCOUNT:4
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P1NODECISION

---

# EmptyHand_NoPromptAtAll
#// CANNOT-PAY IS A DIFFERENT BRANCH FROM DECLINE. With the condition satisfied but an empty hand there
#// is nothing to offer, so no prompt may appear at all — and the "if you do" rider must not fire off an
#// offer that never happened. A handler that queues the choose unconditionally leaves a dangling
#// decision and reds on P1NODECISION.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:4
P1HANDCOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128
P1NODECISION

---

# EmptyDeck_HandCardIsStillResourced
#// The two halves are independent once the "if you do" is satisfied: an empty deck makes the RIDER a
#// clean no-op but must not roll back — or block — the hand card that was already resourced.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:5
P1RESAVAILABLE:4
P1HANDCOUNT:1
P1DECKCOUNT:0
P1NODECISION

---

# Offer_ExactlyTheHandAndNothingElse
#// THE OFFER ITSELF, left pending. The pool is "a card from your hand" — every hand card, and nothing
#// from the deck, the discard or either arena. Three cards in hand so the choice cannot auto-resolve.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Hand: [SOR_095 SEC_080 SOR_128]
WithP1Deck: [SOR_128 SOR_046]
WithP1Discard: SOR_237
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1&myHand-2

---

# TwinSuns_FewerThanAFarSeatOnly_Fires
#// ⚠⚠ THE SEAT-COUNT CELL — THIS SECTION CANNOT PASS AT TWO SEATS, and it is built so the legacy
#// reading gets the OPPOSITE answer rather than merely a narrower one.
#//
#// "AN opponent" here is an EXISTENTIAL CONDITION, not a target: true if ANY live opponent controls
#// more. It must therefore never raise a seat prompt (that would be its own bug). The two-seat shortcut
#// OtherPlayer(1) interrogates seat 2 ALONE — and seat 2 is deliberately the seat with FEWER (4 vs P1's
#// 5), so the legacy reading concludes "not fewer" and the whole ability is skipped. Only seat 3, with
#// 9, satisfies it. Correct: resources move. Legacy: nothing moves.
#//
#// P1 is the ACTOR (there is no WithP3Leader, and CommonSetup dresses seats 1-2 only, so a far seat can
#// never be the one taking the action); seats 3 and 4 carry the far-seat ROLE, which here is just a
#// resource pile. The attack names its target seat explicitly as P2G0.

## GIVEN
CommonSetup: grw/rrk/{myResources:5}
P1OnlyActions: true
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Resources: 4:SOR_046
WithP3Resources: 9:SOR_046
WithP4Resources: 3:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:myHand-0

## EXPECT
SEATCOUNT:4
P2RESCOUNT:4
P3RESCOUNT:9
P4RESCOUNT:3
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:5
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P1NODECISION

---

# TwinSuns_NoOpponentControlsMore_DoesNotFire
#// The far-seat NEGATIVE, on the same four-seat board: P1 ties the richest opponent and beats the rest,
#// so no opponent controls MORE and the condition is false. Proves the existential is "some opponent
#// has more", not "some opponent's count differs from mine".

## GIVEN
CommonSetup: grw/rrk/{myResources:6}
P1OnlyActions: true
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2Resources: 4:SOR_046
WithP3Resources: 6:SOR_046
WithP4Resources: 5:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:P2G0

## EXPECT
SEATCOUNT:4
P1RESCOUNT:6
P1HANDCOUNT:2
P1DECKCOUNT:2
P1NODECISION

---

# ControlChange_NewControllerResolvesItAgainstTheirOwnZones
#// THE CONTROL-CHANGE CELL, and this card has three separate seat-scoped readings riding on it:
#// "you control fewer resources", "YOUR hand" and "YOUR deck". JTL_043 No Glory, Only Results takes
#// control of Ima-Gun Di and defeats him immediately, so P2 is his controller when the When Defeated
#// resolves — P2's resource count is compared, P2 resources a card from P2's hand, and P2's deck gives
#// up its top card. P1 must be untouched in all three zones; asserting only P2 is half a test.
#// P2 controls 5 (enough for JTL_043's cost — paying EXHAUSTS but does not reduce the count) against
#// P1's 8, so the condition holds for P2 and would NOT have held for P1.
#// Ima-Gun Di still goes to P1's discard: control moved, OWNERSHIP did not.
#// ⚠ P1 fields a SECOND unit on purpose. With Ima-Gun Di as the only non-leader unit on the table
#// JTL_043's target auto-resolves, and the answer written for it silently lands on the NEXT prompt —
#// the classic one-spare-answer artifact. Two enemy units make the pick real, so this section also
#// proves JTL_043 took the unit it was aimed at rather than the only one available.

## GIVEN
CommonSetup: grw/bbk/{myResources:8;theirResources:5}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: [JTL_043 SOR_128]
WithP2Deck: [SOR_237 SOR_046]
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: [HMW_044:1:0 SOR_095:1:0]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:HMW_044
P2RESCOUNT:7
P2HANDCOUNT:0
P2DECKCOUNT:1
P2DECKTOPCARD:SOR_046
P1RESCOUNT:8
P1HANDCOUNT:2
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_128

---

# RequestBoundary_RiderStillFires
#// The condition is evaluated and the offer queued in one request; the answer arrives in a FRESH
#// PROCESS, where anything held in an in-memory global is gone. Identical to the opening positive
#// except for the boundary inserted before the answer — the hand card AND the "if you do" top-deck
#// rider must both still land.

## GIVEN
CommonSetup: grw/rrk/{myResources:4}
P1OnlyActions: true
SkipPreGame: true
WithP2Resources: 7:SOR_046
WithP1Hand: [SOR_095 SEC_080]
WithP1Deck: [SOR_128 SOR_046]
WithP1GroundArena: HMW_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0

## EXPECT
P1RESCOUNT:6
P1RESAVAILABLE:4
P1HANDCOUNT:1
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_046
P1NODECISION
