# SEC_120 Naboo Security Force (Ground, 5/7, Command) — When Played/When Defeated: you may disclose
#   Command → give a friendly unit Sentinel for this phase.
# A friendly SOR_095 sits in play. Play SEC_120 → disclose SEC_080 (Command) → choose the friendly
# SOR_095 → it gains Sentinel this phase.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_120
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1NODECISION
