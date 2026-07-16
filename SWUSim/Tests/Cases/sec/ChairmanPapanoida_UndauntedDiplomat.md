# OnDraw_Disclose_CreateSpy
#// SEC_159 Chairman Papanoida (Ground, 2/6, Aggression/Aggression) — When a player draws 1+ cards
#//   during the action phase: you may disclose AggressionAggression → create a Spy token.
#// SEC_159 in play. P1 plays SOR_111 (When Played: draw a card) → the draw fires SEC_159's reaction →
#// disclose two SEC_133 (Aggression each) → create a Spy token. Ground ends with SEC_159 + the Spy.

## GIVEN
CommonSetup: rrw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SEC_159:1:0
WithP1Hand: SOR_111
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP1Deck: [SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_111
P1GROUNDARENACOUNT:2
P1NODECISION
