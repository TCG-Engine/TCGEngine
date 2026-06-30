# SEC_182 Charged with Treason (Event, cost 4, Aggression) — "You may disclose AggressionAggression →
#   deal 5 damage to a unit." Disclose two SEC_133 (Aggression each) → deal 5 to the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: rrk/grw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SEC_182
WithP1Hand: SEC_133
WithP1Hand: SEC_133
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P1NODECISION
