# ExpToForceUnits
#// LOF_099 Paladin Training Corvette — When Played: may give an Experience token to each of up to 3 Force
#// units. P1 controls two Force units (Plo Koon, Youngling Padawan); playing the Corvette gives each an
#// Experience token.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:LOF_099}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: LOF_193:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
