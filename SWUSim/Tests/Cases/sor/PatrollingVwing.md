# WhenPlayedDraw
#// SOR_111 Patrolling V-Wing (Space, 1/1) — When Played: draw a card. Playing it
#// (hand goes 1 → 0) then draws 1 → hand 1; it enters the space arena.
#// COVERAGE: offer=N/A (a bare "draw a card" names no target — there is no pool to offer and no
#//           decision is ever queued; the closest edge, an empty deck, is
#//           EmptyDeck_DrawDealsThreeBaseDamage) · decline=N/A (no "you may" — the draw is
#//           mandatory) · boundary=DrawsExactlyOneCard (deck 2 → exactly 1 drawn, 1 left) +
#//           EmptyDeck_DrawDealsThreeBaseDamage (deck 0 → no card, 3 damage to P1's own base per
#//           CR 6.1) · control=OpponentPlays_OnlyTheOpponentDraws (the hand/deck that move belong
#//           to the seat that PLAYED it; P1's seeded deck and hand are untouched) ·
#//           reqboundary=N/A (the play and its draw resolve inside a single request — no decision
#//           pends between them, proven by every section needing no AnswerDecision)

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1

---

# DrawsExactlyOneCard
#// SOR_111 Patrolling V-Wing — quantity discrimination on the single clause: "Draw a card" draws
#// EXACTLY one, not the whole top of the deck. Deck seeded with two cards → after the play the hand
#// holds 1 and the deck still holds 1.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_111}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1
P1BASEDMG:0

---

# EmptyDeck_DrawDealsThreeBaseDamage
#// Per CR 6.1 the empty-deck rule applies to ANY draw, not just the regroup draw: with no cards left
#// to draw, the When Played draw instead deals 3 damage to P1's own base. Boundary partner of
#// WhenPlayedDraw / DrawsExactlyOneCard (deck 1+ → a real card, no base damage; deck 0 → 3 damage,
#// no card). The unit still enters the space arena — the draw failing does not undo the play.

## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_111}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:0
P1BASEDMG:3

---

# OpponentPlays_OnlyTheOpponentDraws
#// Intended: "Draw a card" is resolved by the player who PLAYED the V-Wing — the deck and hand that
#// move are the controller's, never the other seat's. P2 plays it: P2's deck 1 → 0 and P2's hand
#// ends at 1, while P1's seeded deck and empty hand are untouched.

## GIVEN
CommonSetup: ggk/ggk/{theirResources:2;theirhandCardIds:SOR_111}
SkipPreGame: true
WithActivePlayer: 2
WithP1Deck: SOR_046
WithP2Deck: SOR_095

## WHEN
- P2>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2HANDCOUNT:1
P2DECKCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:1
P1BASEDMG:0
P2BASEDMG:0
