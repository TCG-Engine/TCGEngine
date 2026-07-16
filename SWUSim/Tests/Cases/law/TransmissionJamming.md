# NamedCantBePlayed
#// LAW_243 Transmission Jamming (Cunning event, cost 1) — "Name a card. Cards with that name can't be
#// played this phase." P1 names Battlefield Marine; P2's attempt to play SOR_095 is blocked (stays in hand).

## GIVEN
CommonSetup: yyw/ggw/{myResources:1;theirResources:3}
WithActivePlayer: 1
WithP1Hand: LAW_243
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
