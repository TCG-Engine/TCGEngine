# JTL_250 Sabine's Masterpiece — On Attack: Command + Cunning branches.
# P1 controls a Command unit (SOR_095) and a Cunning unit (SOR_213), no Vigilance/Aggression unit.
# Command → give an Experience token to a unit (SOR_095 → 3/3 becomes 4/4). Cunning → exhaust or
# ready a resource; P1 chooses Exhaust (3 ready → 2 available). Only these two effects fire, in order.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1SpaceArena: JTL_250:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_213:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Exhaust

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:4
P1RESAVAILABLE:2
