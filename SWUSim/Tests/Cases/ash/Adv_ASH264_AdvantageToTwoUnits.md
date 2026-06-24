# ASH_264 A New Order (Event, cost 1) — Give an Advantage token to each of up to 2 units. P1 picks both
# of its units (SOR_095 ground, SOR_237 space); each gains 1 Advantage token.
## GIVEN
CommonSetup: yyw/yyk/{myResources:1;handCardIds:ASH_264}
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&mySpaceArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:1
