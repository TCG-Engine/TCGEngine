# LAW_015 Jabba the Hutt (undeployed leader) — Action [1 resource, Exhaust, return a friendly
# Underworld unit to its owner's hand]: Create a Credit token.
# P1 has one friendly Underworld unit (SOR_247) — the return target auto-resolves. After the action:
# the unit is back in hand, a Credit token exists, the leader is exhausted, and the resource is spent.

## GIVEN
P1LeaderBase: LAW_015/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SOR_247:1:0

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
P1CREDITCOUNT:1
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
