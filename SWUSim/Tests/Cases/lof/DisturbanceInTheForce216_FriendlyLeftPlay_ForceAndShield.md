# LOF_216 Disturbance in the Force — "If a friendly unit left play this phase, the Force is with you and
# you may give a Shield token to a unit." P1's 3/1 attacker dies to counter-damage (a friendly unit left
# play), so playing the event creates the Force and lets P1 shield its surviving SOR_095.

## GIVEN
CommonSetup: yyw/rrk/{myResources:2;handCardIds:LOF_216}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
