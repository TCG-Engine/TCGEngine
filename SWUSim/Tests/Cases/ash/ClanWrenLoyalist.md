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
