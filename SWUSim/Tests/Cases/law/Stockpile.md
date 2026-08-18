# ResourceEventAndTopDeck
#// LAW_171 Stockpile (Command event, cost 6) — "Resource this event and the top card of your deck."
#// Paying 6 exhausts P1's 6 resources; then the event itself + the top deck card become resources
#// (exhausted). Net: 8 resources (all exhausted), deck -1, event NOT in discard.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Deck: SOR_237
WithP1Hand: LAW_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:8
P1RESAVAILABLE:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0

---

# ResourceEventEmptyDeck
#// LAW_171 Stockpile — with an empty deck there is no top card to resource, but the event still resources
#// itself. Paying 6 exhausts 6 resources; Stockpile becomes the 7th (exhausted). Deck and discard stay 0.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:7
P1RESAVAILABLE:0
P1DECKCOUNT:0
P1DISCARDCOUNT:0

---

# ForeignOwnedStockpile_ResourcesForItsControllerFromTheOWNERSDiscard
#// LAW_171 — "Resource this event and the top card of your deck." A spent event goes to its OWNER's
#// discard, which is NOT the caster's pile when the event is played from a foreign zone. P1 uses
#// LAW_215 Vermillion to play a P2-OWNED Stockpile for free off P2's deck.
#// Regression guard: the handler looked for the event in the CASTER's discard only, so for a foreign
#// play the lookup missed and "Resource this event" silently no-opped — half the card was dropped and
#// the event was stranded in P2's discard (observed: P1RESCOUNT 4 instead of 5, P2DISCARDCOUNT 1).
#// DISCRIMINATES: P1RESCOUNT:5 needs BOTH clauses (3 seeded + the event + P1's deck-top). P2DISCARDCOUNT:0
#// proves the event actually left the owner's pile rather than being copied. P2DECKCOUNT:1 proves the
#// second clause read the CONTROLLER's deck, not the owner's.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:JTL_002;myBase:SOR_021;theirBase:SOR_021;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: LAW_215:1:0
WithP1Deck: SOR_237
WithP1Deck: SOR_095
WithP2Deck: LAW_171
WithP2Deck: SOR_164

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:You
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:5
P1DECKCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:0

---

# TheEventItselfIsResourcedFACEDOWN_NotDiscarded
#// LAW_171 Stockpile — "RESOURCE this event and the top card of your deck": the event does not go to the
#// discard pile like a normal event, it becomes a resource. Both new resources arrive EXHAUSTED (a
#// resource added mid-phase is not ready), which is what separates "two resources were added" from "the
#// resource row merely grew". ResourceEventAndTopDeck counts the row; this section pins where the event
#// card ended up and that neither new entry can be spent this phase.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1Hand: LAW_171

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:8
P1RESAVAILABLE:0
P1DISCARDCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:0
