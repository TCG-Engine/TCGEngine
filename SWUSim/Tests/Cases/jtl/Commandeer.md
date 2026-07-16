# ReturnsNextRegroup
#// JTL_235 Commandeer — At the start of the next regroup phase, the commandeered unit returns to its
#// owner's hand. After commandeering SOR_237 and passing to regroup, it leaves P1's arena.

## GIVEN
CommonSetup: ggk/ggk/{myResources:13}
P1OnlyActions: true
WithP1Hand: JTL_235
WithP2SpaceArena: SOR_237:0:0
P1Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]
P2Deck: [SOR_063 SOR_063 SOR_063 SOR_063 SOR_063 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENACOUNT:0

---

# TakeControlReady
#// JTL_235 Commandeer — Take control of a non-leader Vehicle costing 6 or less without a Pilot; ready it.
#// P1 commandeers P2's exhausted SOR_237 (cost 2 Vehicle): it moves to P1's arena, ready.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_235
WithP1Resources: 13
WithP2SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:READY
