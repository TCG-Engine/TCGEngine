# DealsFirstVsExhausted
#// JTL_185 Hound's Tooth — While attacking an exhausted unit that didn't enter play this phase, it deals
#// combat damage before the defender. Hound's Tooth (3 power) attacks the exhausted SOR_225 (2/1):
#// SOR_225 is defeated before it can deal its counter, so Hound's Tooth takes 0 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_185:1:0
WithP2SpaceArena: SOR_225:0:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_185
P1SPACEARENAUNIT:0:DAMAGE:0

---

# SimulateRequestBoundary_EnteredPlayThisPhaseSurvivesRoundTrip
#// JTL_185 Hound's Tooth — "an exhausted unit that DIDN'T enter play this phase" is read from a flag
#// stamped when the defender was played, on an earlier action by the OTHER player. In production those are
#// separate requests, so the flag must live in the gamestate rather than a transient global. P2 plays
#// SOR_225 (2/1, enters exhausted); after the boundary P1's Hound's Tooth (4/3) attacks it. Because the
#// defender DID enter play this phase, Hound's Tooth does NOT deal damage first: they trade simultaneously,
#// so SOR_225 dies AND Hound's Tooth takes its 2. If the flag were lost across the boundary the engine
#// would wrongly grant deals-first and Hound's Tooth would take 0.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: JTL_185:1:0
WithP2Hand: SOR_225
WithP2Resources: 3

## WHEN
- P2>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_185
P1SPACEARENAUNIT:0:DAMAGE:2
