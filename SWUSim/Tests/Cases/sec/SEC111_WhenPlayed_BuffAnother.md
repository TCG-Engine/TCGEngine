# SEC_111 Jar Jar Binks (Ground, 2/1, Command) — When Played: you may give another friendly unit
#   +2/+2 for this phase. P1 plays Jar Jar and buffs SEC_041 (1/4 → 3/6).

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
WithActivePlayer: 1
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:6
