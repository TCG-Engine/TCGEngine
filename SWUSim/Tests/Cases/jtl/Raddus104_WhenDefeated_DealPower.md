# JTL_104 Raddus — When Defeated: Deal damage equal to this unit's power to an enemy unit. Raddus (8/6,
# pre-damaged to 1 remaining, no other Resistance so no Sentinel) attacks SOR_225 and is defeated by the
# counter; its When Defeated deals 8 to the only remaining enemy unit SOR_046 (defeating it).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:5
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
