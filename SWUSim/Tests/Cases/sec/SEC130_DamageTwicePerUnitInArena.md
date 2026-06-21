# SEC_130 Ferrix Uprising (event, cost 4) — Deal damage to a unit equal to twice the number of units you
#   control in its arena. P1 controls 2 ground units → 4 damage to the enemy ground SOR_046.

## GIVEN
CommonSetup: ggk/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_042:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_130

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
