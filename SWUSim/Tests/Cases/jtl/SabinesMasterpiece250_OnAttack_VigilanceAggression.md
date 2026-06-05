# JTL_250 Sabine's Masterpiece — On Attack: for each controlled aspect, its effect.
# P1 controls a Vigilance unit (SOR_046) and an Aggression unit (LAW_180), but no Command/Cunning
# unit. So only the Vigilance (heal 2 from a base) and Aggression (1 to a unit/base) effects fire,
# in printed order. No extra prompts for the absent aspects.

## GIVEN
P1LeaderBase: SOR_002/SOR_021:3
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:1
P2GROUNDARENAUNIT:0:DAMAGE:1
