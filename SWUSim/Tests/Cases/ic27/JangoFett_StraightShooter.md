# Ambush_IsPrinted
#// IC27_144 Jango Fett — Straight Shooter (4/4 Ground, Cunning/Villainy, Separatist/Bounty Hunter, unique)
#// "Ambush
#//  When Defeated: You may discard a card from your hand. If you do, return an event from your discard pile to
#//  your hand."
#// Ambush is printed and auto-wired by the keyword pass. When Defeated: an optional pick over the resolving
#// player's hand (MZMAYCHOOSE); the discard happens FIRST, so a discarded event is itself returnable (CR 6.2:
#// "discard… return" reads in order). The return is MANDATORY ("return an event") over the events in the
#// resolver's OWN discard pile. Nothing is offered when there is no card in hand, or when discarding could not
#// lead to a return (no event in the discard pile and none in hand) — a fizzle-only optional.
#//
#// COVERAGE: keyword=Ambush_IsPrinted + Ambush_AttacksOnPlay ·
#//           positive=WD_DiscardThenReturnChosenEvent ·
#//           offer=WD_DiscardOffer_IsTheWholeHand + WD_ReturnOffer_OwnEventsOnly (units, Jango, P2's events excluded) ·
#//           decline=WD_Decline_KeepsHandAndDiscard ·
#//           cannot-pay=WD_EmptyHand_NoPrompt ·
#//           no-target=WD_NoEventAnywhere_NoPrompt (fizzle-only optional not offered) ·
#//           order=WD_EventOnlyInHand_OfferIsLive + WD_DiscardedEventIsReturnable (discard first, then return) ·
#//           event-in-flight=WD_EventThatDefeatedHimIsReturnable (CR 6.2: an event sits in its owner's discard) ·
#//           singleton=WD_OneEvent_ReturnAutoResolves ·
#//           control=WD_TakenAndDefeatedByNGOR_NewControllerResolves (NGOR's own copy comes back) ·
#//           reqboundary=WD_AnsweredAcrossRequestBoundary ·
#//           modes=2P only ("your hand"/"your discard pile" = the resolving player; no opponent reference)

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_144

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_144
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush

---

# Ambush_AttacksOnPlay
#// Played with an enemy unit on the board: Ambush lets him attack it straight away. Jango (4 power) attacks a
#// Battlefield Marine (3/3) and defeats it, taking 3.

## GIVEN
CommonSetup: yyk/bbw/{myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_144
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:IC27_144
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WD_DiscardThenReturnChosenEvent
#// Jango (4/4) attacks Industrious Team (4/7) and dies. P1 discards Battlefield Marine from hand and returns
#// Shoot First (one of two events in the pile). Discard before: [Repair, Shoot First, Consular SF]; then Jango
#// (3), then the Marine (4). Shoot First leaves the pile.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074,SOR_217,SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myDiscard-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_217
P1DISCARDCOUNT:4
P1DISCARDUNIT:0:CARDID:SOR_074
P1DISCARDUNIT:1:CARDID:SOR_046
P1DISCARDUNIT:2:CARDID:IC27_144
P1DISCARDUNIT:3:CARDID:SOR_095
P1NODECISION

---

# WD_DiscardOffer_IsTheWholeHand
#// "a card from your hand" — any card, unit or event. Two-card hand, left pending.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: [SOR_095 SOR_217]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1
P1HANDCOUNT:2

---

# WD_ReturnOffer_OwnEventsOnly
#// After the discard: only EVENTS in P1's OWN pile are offered — not the Consular Security Force (a unit), not
#// Jango himself, not the discarded Marine, and not the Repair sitting in P2's discard pile. Left pending.
#// Discard: [Repair 0, Consular SF 1, Shoot First 2, Jango 3, Marine 4].

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074,SOR_046,SOR_217;theirDiscardCardIds:SOR_074}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SELECTABLEEXACT:myDiscard-0&myDiscard-2
P1HANDCOUNT:0
P1DISCARDCOUNT:5
P2DISCARDCOUNT:1

---

# WD_Decline_KeepsHandAndDiscard
#// Declining the discard: nothing is discarded and nothing returns.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074,SOR_217}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P1DISCARDCOUNT:3
P1DISCARDUNIT:2:CARDID:IC27_144
P1NODECISION

---

# WD_EmptyHand_NoPrompt
#// The cannot-pay branch: an event waits in the pile, but there is no card to discard, so nothing is offered
#// and the event stays put.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_074

---

# WD_NoEventAnywhere_NoPrompt
#// A card in hand, but no event in the pile and none in hand: discarding could only throw a card away, so the
#// offer is skipped. The Consular Security Force in the pile is a unit, not an event.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1DISCARDCOUNT:2

---

# WD_EventOnlyInHand_OfferIsLive
#// The discard comes first, so an event in HAND makes the offer live even with no event in the pile (the
#// load-bearing half of the fizzle gate: a gate reading only the pile would skip this). Left pending.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_217
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SELECTABLEEXACT:myHand-0

---

# WD_DiscardedEventIsReturnable
#// …and the event just discarded is the one returned (the only event, so the return auto-resolves). The net
#// board equals "nothing happened", so the log carries the proof: a discard and a return both took place.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_217
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1HANDCARD:0:SOR_217
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_046
P1DISCARDUNIT:1:CARDID:IC27_144
LOGCONTAINS:discard pile to their hand

---

# WD_OneEvent_ReturnAutoResolves
#// Discarding a unit with exactly one event in the pile: the mandatory return has a single legal card and
#// takes it without a second prompt.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_046,SOR_074}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NODECISION
P1HANDCOUNT:1
P1HANDCARD:0:SOR_074
P1DISCARDCOUNT:3
P1DISCARDUNIT:0:CARDID:SOR_046
P1DISCARDUNIT:1:CARDID:IC27_144
P1DISCARDUNIT:2:CARDID:SOR_095

---

# WD_EventThatDefeatedHimIsReturnable
#// CR 6.2: a played event is in its owner's discard pile while it resolves. P1 Vanquishes their own Jango
#// (blue base pays Vanquish's Vigilance), discards the Marine, and the only event in the pile — the Vanquish
#// itself — comes back.

## GIVEN
CommonSetup: byk/bbw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: [SOR_078 SOR_095]
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myHand-0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_078
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:IC27_144
P1DISCARDUNIT:1:CARDID:SOR_095

---

# WD_TakenAndDefeatedByNGOR_NewControllerResolves
#// JTL_043 No Glory, Only Results: P2 takes control of P1's Jango, then defeats it — P2 is the controller, so
#// P2 resolves the When Defeated: discards from P2's hand and returns from P2's discard pile, where the No
#// Glory just played sits (CR 6.2). Jango is the only unit, so No Glory's pick auto-resolves. Jango goes to his OWNER's pile (P1). P1's own pile event is untouched.

## GIVEN
CommonSetup: yyk/bbk/{discardCardIds:SOR_074;theirResources:5}
SkipPreGame: true
WithP1GroundArena: IC27_144:1:0
WithP2Hand: [JTL_043 SOR_095]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P2NODECISION
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:JTL_043
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_095
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SOR_074
P1DISCARDUNIT:1:CARDID:IC27_144
P1HANDCOUNT:0

---

# WD_AnsweredAcrossRequestBoundary
#// WD_DiscardThenReturnChosenEvent with a request boundary before each answer.

## GIVEN
CommonSetup: yyk/bbw/{discardCardIds:SOR_074,SOR_217,SOR_046}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_144:1:0
WithP1Hand: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-1

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_217
P1DISCARDCOUNT:4
P1DISCARDUNIT:3:CARDID:SOR_095
P1NODECISION
