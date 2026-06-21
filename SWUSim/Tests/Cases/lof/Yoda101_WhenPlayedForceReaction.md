# LOF_101 Yoda — When Played: You may use the Force. If you do, heal 5 from a base. AND When you use the
# Force: You may deal damage to a unit equal to twice the units you control. P1 plays Yoda (controls the
# Force), uses it, and the use-Force reaction deals 2 (2 × 1 unit = Yoda) to SOR_046.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_101
WithP1Resources: 14
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1NOFORCE
