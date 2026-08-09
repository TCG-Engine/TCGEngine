# Front_Coordinate_SearchRepublic
#// TWI_008 Padmé Amidala (Leader, front) — "Coordinate - Action [1 resource, Exhaust]: Search the top 3
#// cards of your deck for a Republic card, reveal it, and draw it." With 3 units in play (Coordinate active),
#// Padmé's action draws the Republic TWI_109 from the top 3.
## GIVEN
CommonSetup: ggw/rrk/{myResources:1;myLeader:TWI_008}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [TWI_109 SOR_128 SOR_128]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:TWI_109
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:2
