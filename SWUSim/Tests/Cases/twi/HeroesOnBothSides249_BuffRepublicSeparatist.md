# TWI_249 Heroes on Both Sides (Event, Heroism) — "Choose up to 1 Republic unit and up to 1 Separatist
# unit. Give each chosen unit +2/+2 and Saboteur for this phase." TWI_065 (Republic) and TWI_T01 (Separatist
# Battle Droid, 1/1 → 3/3) each gain Saboteur.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TWI_249}
P1OnlyActions: true
WithP1GroundArena: [TWI_065:1:0 TWI_T01:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HASKEYWORD:Saboteur
