# SEC_184 ISB Agent (Ground, 1/3, Cunning/Villainy, cost 1) — When Played: you may reveal an event from
#   your hand. If you do, deal 1 to a unit. SEC_077 (event) is in hand → deal 1 to the enemy.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_184
WithP1Hand: SEC_077

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
