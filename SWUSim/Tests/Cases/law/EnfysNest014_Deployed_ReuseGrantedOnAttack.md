# LAW_014 Enfys Nest (deployed) — an UPGRADE-GRANTED On Attack ability counts as the unit's
# own On Attack ability and is reusable. SOR_214 Smuggling Compartment grants the host
# "On Attack: Ready a resource." The X-Wing attacks P2's base; the granted On Attack readies
# one exhausted resource, and Enfys (deployed, free) uses it again → a second resource readies.
# Starting from 2 exhausted resources, both end ready.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2:SOR_046:0
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:SOR_214

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:2
