# SEC_255 Remote Escort Tank (Ground, 5/5, cost 6) — When Played: give a unit Sentinel for this phase.
#   P1 plays it and grants Sentinel to SEC_041.

## GIVEN
CommonSetup: yyw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_255

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
