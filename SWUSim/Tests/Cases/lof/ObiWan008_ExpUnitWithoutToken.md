# LOF_008 Obi-Wan Kenobi — Action [Exhaust, use the Force]: Give an Experience token to a unit without an
# Experience token on it. Plo Koon (no token) becomes 7/9 and P1 loses the Force.

## GIVEN
P1LeaderBase: LOF_008/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:9
P1NOFORCE
