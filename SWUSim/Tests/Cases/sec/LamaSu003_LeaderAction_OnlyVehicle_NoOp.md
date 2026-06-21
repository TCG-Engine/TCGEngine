# SEC_003 Lama Su (leader) — the upgrade must go on a friendly NON-Vehicle unit. With only a friendly
# Vehicle (SOR_237 X-Wing) in play, there is no valid host: the action is unaffordable → leader stays
# READY, the upgrade stays in hand, resources unspent.

## GIVEN
P1LeaderBase: SEC_003/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_070
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1NODECISION
