# BothDraw2
#// TS26_68 Arms Deal (Event, cost 2, Aggression) — You and an opponent each draw 2 cards.
## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
WithP1Deck: [SEC_080 SOR_095 SOR_046]
WithP2Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:2
P2HANDCOUNT:2
P1DECKCOUNT:1
P2DECKCOUNT:1

---

# ResolvesWithBOTHDecksEmpty
#// TS26_68 Arms Deal — "You and an opponent each draw 2 cards" with nothing to draw on either side. The
#// event still resolves into the discard and neither hand grows; each player instead eats the empty-deck
#// penalty of 3 base damage per undrawn card, so both bases end on 6.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2;handCardIds:TS26_68}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:0
P1DISCARDCOUNT:1
P1BASEDMG:6
P2BASEDMG:6
