# OpponentTakesControl
#// SHD_256 Mercenary Gunship (3/2 Space) — "Action [4 resources]: Take control of this unit. Any player
#// may use this ability." P1 controls the Gunship; on P2's turn, P2 (the opponent) pays 4 resources to use
#// the action and takes control of it. The unit moves to P2's space arena; P2 spends 4 of its 5 resources.
#// COVERAGE: offer=Unaffordable_NoOp (the action is not offered without 4 ready resources — asserted as an
#//           untouched board rather than a pool, since an unofferable action produces no decision) ·
#//           decline=N/A (an Action ability is opt-in by nature; not using it is the null case) ·
#//           control=OpponentTakesControl (this IS the control-change axis) + PilotedLeaderUnit_Defeated
#//           InsteadOfChangingControl (per CR, a leader unit is defeated rather than changing control) ·
#//           boundary=affordable (OpponentTakesControl) vs one short (Unaffordable_NoOp) ·
#//           reqboundary=N/A (a single action resolves the whole ability)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:5
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SHD_256:1:0

## WHEN
- P2>UseUnitAbility:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_256
P2RESAVAILABLE:1

---

# Unaffordable_NoOp
#// SHD_256 Mercenary Gunship (3/2 Space) — the take-control action costs 4 resources. With only 3 ready
#// resources, P2 cannot afford it: the action is not offered and using it is a clean no-op — P1 keeps
#// control of the Gunship and P2's resources are untouched.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:3
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SHD_256:1:0

## WHEN
- P2>UseUnitAbility:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_256
P2SPACEARENACOUNT:0
P2RESAVAILABLE:3

---

# PilotedLeaderUnit_ChangesControl_PilotStaysAttached
#// P1's leader JTL_001 is deployed as a Pilot on the Gunship, making the Gunship a leader unit. P2 pays the 4
#// resources for the any-player take-control action: the Gunship moves to P2 carrying P1's Pilot leader.
#// CR v9.0 3.4.7 (rewritten 2026): "Some abilities make non-leader units leader units ... it doesn't follow
#// rules 3.4.1-3.4.6. ... it can change control or move to an out-of-play zone". A unit made a leader by a
#// Pilot leader is NOT defeated instead (that is 3.4.6, for real leader units). Judges' discussion
#// 2026-10-01: "you're giving control of the unit, and not the leader upgrade" — the Pilot leader stays
#// attached, still controlled by its own player, so that leader stays DEPLOYED. (Before v9 this section
#// asserted the unit was defeated instead.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myLeaderDeployedPilot:1;
  myBase:SOR_021;
  theirBase:SOR_021;
  theirResources:5
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SHD_256:1:0

## WHEN
- P2>UseUnitAbility:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_256
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P1DISCARDCOUNT:0
P1LEADER:DEPLOYED
P2RESAVAILABLE:1

---

# TwinSuns_OfferedOnEVERYSeatsBoardNotJustOne
#// ⚠ THE SEAT-COUNT CELL for the offer path — added 2026-08-24. SHD_256 is the second "Any player may use
#// this ability" card (with LAW_156), and both depend on SWUComputeActionsData surfacing the action on a
#// board the actor does not control.
#// That surfacing used `$oppAP = OtherPlayer($player)` — ONE seat — so above two seats the action was
#// simply ABSENT from the other boards. For a "take control of this unit" card that is a silent,
#// unreachable ability with no prompt for anyone to notice missing.
#// ⚠ Every other section on this card drives `UseUnitAbility` directly and bypasses the offer list
#//   entirely, which is why this defect survived until the P#UNITACTIONS assertion existed.
#// SEAT 3 and SEAT 4 each control a Mercenary Gunship; P2 is active with resources for the [4 resources]
#// cost, so BOTH must appear in P2's offer list.
#// ⚠ A 2-player version CANNOT FAIL — with one opponent OtherPlayer() already names the only board.
#// Mutation check: revert the OpponentsOf() loop to OtherPlayer() and one of the two reds.

## GIVEN
CommonSetup: bbk/bbk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 2
WithGamePhase: ActionPhase
WithP2Resources: 6
WithP3SpaceArena: SHD_256:1:0
WithP4SpaceArena: SHD_256:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN

## EXPECT
SEATCOUNT:4
P2UNITACTIONSHAS:p3SpaceArena-0&p4SpaceArena-0
