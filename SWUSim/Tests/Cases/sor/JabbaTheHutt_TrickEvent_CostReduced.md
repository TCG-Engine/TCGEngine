# SOR_181 Jabba the Hutt — passive: "Each TRICK event you play costs 1 less." With Jabba in play, P1
# plays SOR_222 (Return a non-leader unit to hand — a Trick event, cost 3 Cunning) for 2 (3 ready
# resources → 1 left). Two non-leader units are in play (Jabba + enemy SOR_128); P1 bounces the enemy.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_181:1:0
WithP2GroundArena: SOR_128:1:0
WithP1Hand: SOR_222

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
