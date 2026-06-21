# LAW_176 Sebulba's Podracer — "may" decline branch: same setup, but P1 declines the ready (NO), so
# the Podracer stays EXHAUSTED.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_176:0:0
WithP1GroundArena: LAW_173:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_176
P1GROUNDARENAUNIT:0:EXHAUSTED
