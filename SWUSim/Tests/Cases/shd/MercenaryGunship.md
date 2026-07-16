# OpponentTakesControl
#// SHD_256 Mercenary Gunship (3/2 Space) — "Action [4 resources]: Take control of this unit. Any player
#// may use this ability." P1 controls the Gunship; on P2's turn, P2 (the opponent) pays 4 resources to use
#// the action and takes control of it. The unit moves to P2's space arena; P2 spends 4 of its 5 resources.

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
