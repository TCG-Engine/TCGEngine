# SOR_184 Fett's Firespray — the Action costs 2 resources; with only 1 ready resource it's a full
# no-op: the enemy unit stays READY and resources are unchanged.

## GIVEN
CommonSetup: ryk/brw/{
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_184:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 1

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1
