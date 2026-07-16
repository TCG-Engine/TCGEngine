# DrawOnPlay
#// ASH_087 Cybernetic Enhancements (Upgrade, cost 3) — When Played: draw a card. Played onto SOR_095, P1
#// draws SEC_080 from the top of the deck.
## GIVEN
CommonSetup: bbk/bbk/{myResources:3;handCardIds:ASH_087}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SEC_080
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
