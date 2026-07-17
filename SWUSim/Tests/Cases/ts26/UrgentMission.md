# Deal2OwnBaseDraw2
#// TS26_64 Urgent Mission (Event, cost 2, Aggression/Heroism) — Deal 2 damage to your base. Draw 2 cards.
## GIVEN
CommonSetup: rgw/rrk/{myResources:2}
WithP1Hand: TS26_64
WithP1Deck: [SEC_080 SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:2
P1HANDCOUNT:2
P1DECKCOUNT:1
