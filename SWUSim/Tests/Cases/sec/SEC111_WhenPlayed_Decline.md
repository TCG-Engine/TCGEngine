# SEC_111 Jar Jar Binks — the When-Played buff is a "may". Declining leaves SEC_041 at its base 1/4.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:4
