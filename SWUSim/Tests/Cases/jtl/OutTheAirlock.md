# MinusFive
#// JTL_079 Out the Airlock (event) — Give a unit -5/-5 for this phase. JTL_069 (4/7) drops to 0 power
#// (floored) and 2 HP.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_079
WithP1Resources: 5
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:0
P1SPACEARENAUNIT:0:HP:2

---

# SimulateRequestBoundary_PhaseDebuffSurvives
#// JTL_079 Out the Airlock — the -5/-5 lasts "for this phase", so it must be recorded in the serialized
#// gamestate rather than an in-memory global: in production the very next request (any action by either
#// player) re-parses the gamestate from disk, and a transient-only debuff would silently evaporate.
#// Mirrors MinusFive with a request boundary after the event resolves.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_079
WithP1Resources: 5
WithP1SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:POWER:0
P1SPACEARENAUNIT:0:HP:2
