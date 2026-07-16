# WhenPlayedReadyBountyHunter
#// LAW_061 Asajj Ventress (3/3) — When Played: you may ready another Bounty Hunter unit. Ready the
#// exhausted LAW_124 (Bounty Hunter).

## GIVEN
CommonSetup: grw/bgw/{myResources:5}
WithP1GroundArena: LAW_124:0:0
WithP1Hand: LAW_061

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_124
P1GROUNDARENAUNIT:0:READY
