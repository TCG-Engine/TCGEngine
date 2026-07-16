# Event_TokenWithSentinel
#// ASH_091 Buy Time (Event) — create a Mandalorian token and give it Sentinel for this phase.

## GIVEN
CommonSetup: yrw/grw/{myResources:6;handCardIds:ASH_091}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
