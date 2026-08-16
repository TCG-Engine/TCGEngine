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

# PilotedLeaderUnit_DefeatedInsteadOfChangingControl
#// Per CR 3.4.6: if an ability would make a LEADER UNIT change control, it is defeated instead. P1's
#// leader JTL_001 is deployed as a Pilot upgrade onto the Gunship, which makes the Gunship a leader unit.
#// P2 then pays the 4 resources for the any-player take-control action: control does NOT transfer —
#// the Gunship goes to its owner's discard and the Leader Upgrade flips back to P1's leader zone
#// EXHAUSTED. Both arenas end empty, and P2 still paid the cost.

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
P2SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_256
P1LEADER:NOTDEPLOYED
P1LEADER:EXHAUSTED
P2RESAVAILABLE:1
