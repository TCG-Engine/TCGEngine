# SHD_251 The Mandalorian's Rifle — "When Played: If attached unit is The Mandalorian, he captures an
# exhausted enemy non-leader unit." Played onto SHD_049 (The Mandalorian); the exhausted enemy SHD_095
# is captured (removed from its arena).

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_049:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
