# TWI_025 Shadow Collective Camp (Base, 25 HP) — "When you deploy a leader: Draw a card." Deploying Luke
# draws a card.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;myBase:TWI_025}
P1OnlyActions: true
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>DeployLeader

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1
