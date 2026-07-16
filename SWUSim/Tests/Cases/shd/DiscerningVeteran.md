# WhenPlayed_CaptureEnemy
#// SHD_120 Discerning Veteran (5-cost, Command ground) — "When Played: This unit captures an enemy non-leader
#// ground unit." Playing it captures the enemy SOR_046 (removed from P2's arena, held as a captive under the
#// Veteran).

## GIVEN
CommonSetup: ggk/ggk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SHD_120
