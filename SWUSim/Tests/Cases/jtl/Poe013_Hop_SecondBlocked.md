# JTL_013 Poe Dameron (LEADER) — once-per-round hop guard: second hop is blocked.
# Setup: two SOR_225 (TIE/ln Fighter) in Space arena. Poe pre-attached to index-0 via WithP1SpaceArenaUpgrade.
# With 2 ready resources: first hop (UseUnitAbility on index-0 host) → Poe moves to index-1, costs 1 resource.
# Second hop attempt (UseUnitAbility on index-1 host) → guard fires (SWU_POE_013_HOP_USED is set),
# SWUUnitActionAffordable returns false, action is a no-op; no resource spent.
# Assert: Poe still on index-1, P1RESAVAILABLE:1, no pending decision.

## GIVEN
P1LeaderBase: JTL_013/SOR_022
P2LeaderBase: JTL_013/SOR_022
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>UseUnitAbility:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1RESAVAILABLE:1
P1NODECISION
