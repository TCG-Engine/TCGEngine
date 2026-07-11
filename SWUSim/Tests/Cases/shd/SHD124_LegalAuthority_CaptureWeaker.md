# SHD_124 Legal Authority — "When Played: Attached unit captures an enemy non-leader unit with less
# power than it." Played onto SOR_095 (3 power); the enemy SHD_095 (2 power < 3) is captured (removed
# from its arena, held facedown under the host).

## GIVEN
CommonSetup: ggw/ggw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SHD_124
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
