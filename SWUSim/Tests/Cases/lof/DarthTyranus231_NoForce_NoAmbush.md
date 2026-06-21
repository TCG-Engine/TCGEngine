# LOF_231 Darth Tyranus — the Ambush is conditional on "While the Force is with you." With NO Force,
# Tyranus has only his innate Shielded: playing him gives a shield and adds NO Ambush entry trigger
# (so no attack into the enemy unit, and he does not have the Ambush keyword). Absence guard for the
# conditional keyword grant.

## GIVEN
P1LeaderBase: SOR_002/LOF_026
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: LOF_231
WithP1Resources: 8
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NOFORCE
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_231
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:NOTKEYWORD:Ambush
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
