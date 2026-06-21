# LAW_015 Jabba (undeployed) — the "return a friendly Underworld unit" additional cost is unpayable
# when no friendly Underworld unit is in play, so the action is a full no-op: the leader stays ready,
# the resource is kept, no Credit is created, and the player keeps their action.
# P1's only unit is SEC_080 (Imperial/Droid/Trooper — NOT Underworld).

## GIVEN
P1LeaderBase: LAW_015/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility:0

## EXPECT
P1LEADER:READY
P1RESAVAILABLE:1
P1CREDITCOUNT:0
P1GROUNDARENACOUNT:1
P1NODECISION
