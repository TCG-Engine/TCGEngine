# JTL_001 Asajj Ventress (leader) — Action [Exhaust]: Deal 1 damage to a friendly unit. If you do,
# deal 1 damage to an enemy unit in the same arena. P1's only friendly unit (SEC_080, ground) takes 1,
# then the only enemy unit in the SAME (ground) arena (SOR_095) takes 1. Both auto-resolve (1 each).

## GIVEN
P1LeaderBase: JTL_001/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED
