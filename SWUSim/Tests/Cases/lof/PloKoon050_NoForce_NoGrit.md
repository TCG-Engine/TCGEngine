# LOF_050 Plo Koon (6/8) — negative: without the Force he has no Grit, so 3 damage does not raise his
# power (stays 6).

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithP1GroundArena: LOF_050:1:3

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6
