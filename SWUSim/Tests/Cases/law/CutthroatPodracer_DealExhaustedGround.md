# LAW_213 Cutthroat Podracer (Cunning,Villainy, cost 4) — When Played: you may deal 2 damage to an
# exhausted ground unit. Hit the exhausted enemy SEC_080.

## GIVEN
CommonSetup: yyk/bgw/{myResources:4}
WithP2GroundArena: SEC_080:0:0
WithP1Hand: LAW_213

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
