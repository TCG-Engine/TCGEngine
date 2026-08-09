# EachPlayerResourcesTop
#// TS26_56 Galactic Escalation (Event, cost 2, Command) — Each player resources the top card of their
#// deck. Both P1 and P2 gain a resource and lose their deck's top card.
## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_56}
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SOR_046 SOR_128]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:3
P2RESCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1

---

# OnlyTheOpponentHasADeck
#// TS26_56 Galactic Escalation — "EACH player resources the top card of THEIR deck", resolved per player.
#// With P1's deck empty only P2 resources: P2 goes 1 -> 2 and their deck 2 -> 1, while P1 keeps the 2 it
#// paid down to.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_56}
SkipPreGame: true
P1OnlyActions: true
WithP2Deck: [SOR_046 SOR_128]

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:2
P2RESCOUNT:2
P2DECKCOUNT:1

---

# OnlyThePlayerHasADeck
#// TS26_56 Galactic Escalation — the mirror: with P2's deck empty only P1 resources, going 2 -> 3 with
#// their deck 2 -> 1 while P2 stays at 1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_56}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:3
P2RESCOUNT:1
P1DECKCOUNT:1

---

# BothDecksEmpty_TheEventStillResolves
#// TS26_56 Galactic Escalation — with nothing to resource on either side the event is simply played and
#// discarded; neither resource count moves and nothing errors.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_56}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:2
P2RESCOUNT:1
P1DISCARDCOUNT:1
