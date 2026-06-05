# JTL_017 Han Solo (leader) — the +1/+0 requires the revealed card and the unit to have DIFFERENT odd
# costs. Both the revealed card and the attacker are JTL_069 (cost 5, odd) — same odd cost, so no buff:
# the attacker deals its base 4 to P2's base.

## GIVEN
P1LeaderBase: JTL_017/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1Deck: JTL_069

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:POWER:4
P1LEADER:EXHAUSTED
