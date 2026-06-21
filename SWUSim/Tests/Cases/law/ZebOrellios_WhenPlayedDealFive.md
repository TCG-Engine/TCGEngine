# LAW_045 Zeb Orellios (4/4, Sentinel) — When Played: deal 3 to a ground unit (5 instead if you control
# a Command or Cunning unit). P1 controls SOR_095 (Command) -> deal 5 to the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: brw/bgw/{myResources:5}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_045

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5
