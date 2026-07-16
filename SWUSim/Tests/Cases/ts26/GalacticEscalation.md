# EachPlayerResourcesTop
#// TS26_056 Galactic Escalation (Event, cost 2, Command) — Each player resources the top card of their
#// deck. Both P1 and P2 gain a resource and lose their deck's top card.
## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_056}
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
