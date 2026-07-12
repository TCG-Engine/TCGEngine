# TWI_074 Guarding the Way (Event, cost 2, Vigilance) — "Give a unit Sentinel for this phase. If you
# have the initiative, also give that unit +2/+2 for this phase." With the initiative, the chosen
# SOR_046 (3/7) gains Sentinel AND becomes 5/9.

## GIVEN
CommonSetup: bbw/grw/{myResources:2;handCardIds:TWI_074}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
