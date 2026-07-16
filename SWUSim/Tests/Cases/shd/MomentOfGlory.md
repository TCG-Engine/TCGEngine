# MomentOfGlory_Buff4_4
#// SHD_130 Moment of Glory — "Give a unit +4/+4 for this phase." SOR_046 (3/7) → 7/11.

## GIVEN
CommonSetup: ggw/ggw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_130
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11
