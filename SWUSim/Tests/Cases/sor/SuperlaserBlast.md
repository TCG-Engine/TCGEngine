# DefeatsAllUnits
#// SOR_043 Superlaser Blast (event, cost 8) — "Defeat all units." Every unit across both players' ground
#// and space arenas is defeated simultaneously; the event goes to discard.
#// COVERAGE: offer=N/A ("all units" names no choice — there is no pool to select from;
#//           EmptyBoard_NoOp guards the no-prompt with P1NODECISION) · decline=N/A (a mandatory
#//           event effect, and the event has no optional cost) ·
#//           control=ControlledEnemyUnit_GoesToItsOwnersDiscard (the wipe ignores the controller
#//           and each card is put into its OWNER'S discard) · boundary=EmptyBoard_NoOp (0 units)
#//           vs DefeatsAllUnits (4 units across both players and both arenas), plus
#//           DefeatsDeployedLeader (a leader unit is a unit) and ShieldsDoNotSaveUnits (defeat is
#//           not damage, so a Shield prevents nothing) ·
#//           reqboundary=N/A (the whole wipe resolves inside the single play request)

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

---

# ShieldsDoNotSaveUnits
#// SOR_043 Superlaser Blast — "Defeat all units" is a DEFEAT, not damage, so a Shield token has
#// nothing to prevent: every shielded unit is defeated alongside the bare ones and no Shield is
#// spent to stop it. P1 fields a shielded ground unit and a shielded space unit, P2 a shielded
#// ground unit plus a bare space unit. All four arenas empty out and neither base takes a point
#// (nothing redirects).

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
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION

---

# ControlledEnemyUnit_GoesToItsOwnersDiscard
#// SOR_043 Superlaser Blast — "all units" ignores who CONTROLS them, and a defeated card is put
#// into its OWNER'S discard pile. P1 controls a unit OWNED by P2 (the state after a take-control
#// effect). The blast defeats it along with everything else, and the card lands in P2's discard,
#// not the controller's: P1's discard holds only the event it just played.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_043
WithP1Resources: 8
WithP1GroundArenaControlled: SEC_080:2    # P1-controlled, P2-owned

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_043
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SEC_080
