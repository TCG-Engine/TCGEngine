# JTL_018 Kazuda Xiono (undeployed) — Leader Action [Exhaust]: a friendly unit loses all abilities for
# this round; take an extra action. P1 has one friendly unit (SOR_063, innate Sentinel). The action
# auto-targets it (it loses Sentinel). Then, because Kazuda grants an EXTRA action (no turn swap), the
# same player immediately attacks with SOR_063 into P2's base for 2 — proving the turn didn't pass.

## GIVEN
P1LeaderBase: JTL_018/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:2
