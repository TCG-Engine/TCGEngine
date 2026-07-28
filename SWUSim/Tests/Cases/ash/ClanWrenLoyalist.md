# SearchTraitMatch
#// ASH_107 Clan Wren Loyalist (Ground, 3/2, Mandalorian/Trooper) — When Played: search the top 5 of your
#// deck for a card that shares a Trait with a unit you control, reveal it, and draw it. Clan Wren (a
#// Trooper) finds SEC_080 (a Trooper) and draws it.
## GIVEN
CommonSetup: ggw/ggk/{myResources:3;handCardIds:ASH_107}
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SEC_080
## EXPECT
P1HANDCOUNT:1

---

# NoTraitMatch_NoDraw
#// ASH_107 Clan Wren Loyalist — the search only draws a card sharing a Trait with a unit you control. Clan
#// Wren (Mandalorian/Trooper) is the only unit; the top card SOR_237 (Vehicle/Fighter) shares neither, so
#// nothing is drawn.
## GIVEN
CommonSetup: ggw/ggk/{myResources:3;handCardIds:ASH_107}
WithP1Deck: SOR_237
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0

---

# SearchDeclineTakeNothing
#// ASH_107 Clan Wren Loyalist — the When Played search is optional; you may take nothing even when a valid
#// card is found. Clan Wren (a Trooper) finds SEC_080 (a Trooper) in the top 5 but P1 declines, so nothing
#// is drawn and SEC_080 stays in the deck.
## GIVEN
CommonSetup: ggw/ggk/{myResources:3;handCardIds:ASH_107}
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:1

---

# EmptyDeck_NoEffect
#// ASH_107 Clan Wren Loyalist — with an empty deck there is nothing to search, so the When Played ability
#// does nothing and no decision is raised.
## GIVEN
CommonSetup: ggw/ggk/{myResources:3;handCardIds:ASH_107}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
P1NODECISION
