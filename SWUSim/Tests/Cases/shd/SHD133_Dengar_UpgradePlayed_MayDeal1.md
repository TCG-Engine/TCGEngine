# SHD_133 Dengar — "When you play an upgrade on a unit: You may deal 1 damage to that unit." With Dengar
# in play, P1 plays SOR_069 onto SOR_046; Dengar's reaction deals 1 to SOR_046.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SHD_133:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
