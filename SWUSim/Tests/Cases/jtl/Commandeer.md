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

---

# DefeatedStaysInDiscard
#// JTL_235 Commandeer — the delayed "return that unit to its owner's hand at the next regroup" must NOT
#// resurrect a unit that was defeated in the meantime. P1 commandeers P2's SOR_237 (now P1-controlled);
#// P2 (the owner) then plays SOR_078 Vanquish on it, sending it to P2's discard. At the next regroup the
#// return effect finds nothing to move: SOR_237 stays in P2's discard and does NOT come back to P2's hand.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Hand: JTL_235
WithP1Resources: 13
WithP2SpaceArena: SOR_237:0:0
WithP2Hand: SOR_078
WithP2Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2DISCARDUNIT:1:CARDID:SOR_237
P2HANDCOUNT:0
