# SpaceThenGround
#// JTL_124 Tandem Assault — Attack with a space unit, then a ground unit (+2/+0). SOR_237 (space, 2) hits
#// the enemy space unit for 2; the chained ground attacker SOR_063 (2+2) hits the enemy ground unit for 4.

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_124
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# SimulateRequestBoundary_ChainedGroundBuff
#// JTL_124 Tandem Assault — the space attack's target decision ends the request in production, so the
#// chained ground attack (and the +2/+0 it grants "for this attack") is set up in one process and answered
#// in a fresh one. Mirrors SpaceThenGround with the boundary inserted between the two attack-target
#// answers: the queued "attack with a ground unit" continuation and its +2/+0 must survive serialization
#// (SOR_063 must still hit for 4, not its printed 2).

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_124
WithP1Resources: 1
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_063:1:0
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:4
