# TS26_070 Backed by Black Sun (Event, cost 3) — Deal 1 damage to an enemy unit. You may deal damage
# to a unit equal to the number of damaged enemy units.
# Enemy A (LAW_124, 4/7) starts pre-damaged 1. Deal 1 to enemy B (now both enemies damaged → 2
# damaged enemy units). The optional bonus deals 2 to A (1 + 2 = 3 damage).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:TS26_070}
WithP2GroundArena: [LAW_124:1:1 LAW_124:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:1
