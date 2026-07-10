# SHD_256 Mercenary Gunship (3/2 Space) — the take-control action costs 4 resources. With only 3 ready
# resources, P2 cannot afford it: the action is not offered and using it is a clean no-op — P1 keeps
# control of the Gunship and P2's resources are untouched.

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
