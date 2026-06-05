# SOR_146 Zeb Orrelios — the ability is conditional on the defender being defeated. Zeb (5 power)
# attacks a 3/7 (SOR_046) that survives, so the defender was NOT defeated: no may-choose is queued
# (P1NODECISION) and the defender takes only the 5 combat damage (not 5 + 4). Proves the gate.

## GIVEN
P1LeaderBase: SOR_011/SOR_025
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_146:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P1GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
