# DefeatsAllUnits
#// SOR_043 Superlaser Blast (event, cost 8) — "Defeat all units." Every unit across both players' ground
#// and space arenas is defeated simultaneously; the event goes to discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 8
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDCOUNT:3
P2DISCARDCOUNT:2

---

# DefeatsDeployedLeader
#// SOR_043 Superlaser Blast — "all units" includes a deployed leader unit, which is defeated and returns
#// to its leader zone (NOTDEPLOYED). P1 deploys its leader, then plays Superlaser Blast.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 13
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>DeployLeader
- P1>PlayHand:0

## EXPECT
P1LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# EmptyBoard_NoOp
#// SOR_043 Superlaser Blast — with no units in play it resolves cleanly (no crash, no decision) and goes
#// to the discard.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 8

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1NODECISION
