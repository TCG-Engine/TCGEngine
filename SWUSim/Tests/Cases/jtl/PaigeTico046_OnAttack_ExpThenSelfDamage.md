# JTL_046 Paige Tico (pilot) — Attached gains "On Attack: give an Experience token to this unit, then
# deal 1 to it." Host SOR_237 power 2 + pilot upgradePower 2 = 4, +Exp token +1 → 5, then 1 self-damage.

## GIVEN
P1LeaderBase: JTL_001/SOR_020
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:POWER:5
