# SEC_084 Mas Amedda (Ground, 3/4, Command/Villainy) — When Played: give an Experience token to each of
#   up to 2 OTHER Official units. (Plot auto.) Two friendly Official units (SEC_041) each get +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_084

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION
