# SHD_251 — only EXHAUSTED enemies can be captured. With the only enemy ready (SHD_095 status 1), the
# whenPlayed finds no valid target: the upgrade attaches but nothing is captured.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SHD_049:1:0
WithP1Hand: SHD_251
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
