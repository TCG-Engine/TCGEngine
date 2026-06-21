# SEC_038 Condemn — the suppression is attack-scoped ("WHILE attached unit is attacking"). A Condemn-
#   bearing Sentinel unit that is NOT attacking keeps all its abilities: SOR_063 (2/4 Sentinel) with a
#   Condemn still HAS Sentinel while idle. Guard against the lose-abilities applying continuously.

## GIVEN
CommonSetup: ggw/grk
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 0:SEC_038

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
