# LOF_031 Karis (2/4) — When Defeated: you may use the Force → give a unit -2/-2 for this phase. Karis
# attacks a 4/7 and dies to the 4 counter-damage; on death P1 uses the Force (the lone remaining unit,
# the 4/7, auto-takes -2/-2 → power 4 → 2).

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_031:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1NOFORCE
P2GROUNDARENAUNIT:0:POWER:2
