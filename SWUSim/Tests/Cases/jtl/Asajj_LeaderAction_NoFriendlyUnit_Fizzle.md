# JTL_001 Asajj Ventress (leader) — with no friendly unit to damage, the whole ability fizzles
# (you can't "deal 1 to a friendly", so the "if you do" enemy half never happens). The leader still
# spends its action (exhausts), the enemy unit is untouched, and no decision is left pending.

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
P1NODECISION
