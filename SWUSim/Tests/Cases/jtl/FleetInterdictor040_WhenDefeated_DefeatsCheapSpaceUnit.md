# JTL_040 Fleet Interdictor — When Defeated: You may defeat a space unit that costs 3 or less. JTL_040
# (6/6, pre-damaged to 1 remaining HP) attacks SOR_225 and is defeated by the counter (SOR_225 is also
# defeated by JTL_040's 6 power). JTL_040's When Defeated then lets P1 defeat the remaining cost-2
# SOR_237. (Driven by the active player so the combat whenDefeated orchestration resolves cleanly.)

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_040:1:5
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
