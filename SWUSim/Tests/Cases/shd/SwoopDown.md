# SpaceAttacksGround
#// SHD_230 Swoop Down (Event, cost 1, Cunning) — "Attack with a space unit. It gains Saboteur and can
#// attack ground units for this attack. If it attacks a ground unit, it gets +2/+0 and the defender gets
#// -2/-0 for this attack." P1's SOR_050 (5/5 space) attacks the enemy SOR_046 (3/7 ground): with +2 it
#// deals 7 and defeats it (only 5 without the bonus), and the defender's -2/-0 cuts its counter to 1.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SHD_230
WithP1SpaceArena: SOR_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:1
