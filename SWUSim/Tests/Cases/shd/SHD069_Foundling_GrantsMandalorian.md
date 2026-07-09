# SHD_069 Foundling — "Attached unit gains the Mandalorian trait." Proven behaviorally: a vanilla
# non-Mandalorian unit (SOR_046) wearing Foundling counts as a Mandalorian, so playing SHD_073
# Mandalorian Armor onto it grants a Shield (the "if attached unit is a Mandalorian" branch fires).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_069
WithP1Hand: SHD_073

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
