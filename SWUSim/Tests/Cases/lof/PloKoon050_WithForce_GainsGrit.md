# LOF_050 Plo Koon (6/8) — "While the Force is with you, this unit gains Grit." With the Force and 3
# damage on him, Grit is active: power 6 + 3 (one per damage) = 9.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithP1Force: true
WithP1GroundArena: LOF_050:1:3

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:9
