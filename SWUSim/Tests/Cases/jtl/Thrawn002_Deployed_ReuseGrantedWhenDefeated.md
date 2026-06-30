# JTL_002 Grand Admiral Thrawn (deployed) — an UPGRADE-GRANTED When Defeated ability counts as
# the unit's own and is reusable. LAW_141 Targeted For Removal grants the host
# "When Defeated: An opponent creates Credit tokens equal to this unit's cost."
# P1's SOR_128 (cost 1) carries LAW_141 and attacks LAW_124 (4/7) — it survives the hit and
# counters for lethal, so SOR_128 dies as the attacker (When Defeated resolves inline).
# The granted When Defeated gives P2 1 Credit; Thrawn (deployed, free, once/round) uses it again
# → P2 gets a second Credit. P2 ends with 2 Credit tokens.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP1GroundArenaUpgrade: 0:LAW_141
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2CREDITCOUNT:2
