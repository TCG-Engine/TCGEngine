# LAW_124 Industrious Team (4/7, cost 8) — When Played: you may defeat a non-leader unit with 4 or less
# remaining HP. SEC_080 (3/3, remaining 3) qualifies; the Team (7 HP) does not.

## GIVEN
CommonSetup: bbw/bgw/{myResources:8}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_124
