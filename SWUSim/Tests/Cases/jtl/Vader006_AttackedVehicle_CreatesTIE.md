# JTL_006 Darth Vader (leader) — Action [Exhaust]: If you attacked with a non-token Vehicle unit this
# phase, create a TIE Fighter token. P1's X-Wing (SOR_237, a non-token Vehicle) attacks P2's base, then
# Vader's action creates a TIE Fighter (JTL_T01) in the space arena.

## GIVEN
P1LeaderBase: JTL_006/JTL_022
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:JTL_T01
P2BASEDMG:2
P1LEADER:EXHAUSTED
