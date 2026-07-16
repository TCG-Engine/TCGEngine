# TakeControlAndDebuff
#// ASH_200 Rehabilitation (Event, cost 5) — Choose a non-leader unit; give it -3/-0 for this phase, then
#// take control of it (until regroup). P1 takes control of the enemy SEC_135 (4/3 → 1 power), moving it into
#// P1's ground arena.
## GIVEN
CommonSetup: yyk/yyk/{myResources:5;handCardIds:ASH_200}
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:1
