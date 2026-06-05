# JTL_012 Luke Skywalker (leader) — Action [Exhaust]: If you attacked with a Fighter unit this phase,
# deal 1 damage to a unit. P1's X-Wing (SOR_237, a Fighter) attacks P2's base, then Luke's action deals
# 1 to the enemy SOR_095.

## GIVEN
P1LeaderBase: JTL_012/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:2
P1LEADER:EXHAUSTED
