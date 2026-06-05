# JTL_015 Rio Durant (leader) — Action [1 resource, Exhaust]: Attack with a space unit. It gets +1/+0
# and gains Saboteur for this attack. The X-Wing (SOR_237, power 2) attacks P2's base for 2+1=3. The
# +1/+0 and Saboteur are both "for this attack" only, so afterwards the X-Wing is back to power 2 with
# no Saboteur.

## GIVEN
P1LeaderBase: JTL_015/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:NOTKEYWORD:Saboteur
P1RESAVAILABLE:0
P1LEADER:EXHAUSTED
