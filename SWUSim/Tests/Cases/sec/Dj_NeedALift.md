# LeaderAction_PlayUnitCaptured
#// SEC_018 DJ (leader) — Action [Exhaust]: Choose a friendly unit. Play a unit from your hand (costs 1
#// less). The chosen unit captures it. P1's SOR_095 (the captor) captures the just-played SOR_128, so
#// SOR_128 is NOT a separate arena unit (ground count stays 1) — it rides SOR_095 as a captive subcard.
#// Generous resources avoid aspect-penalty math on the played unit.

## GIVEN
CommonSetup: byw/bbk/{
  myLeader:SEC_018;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: SOR_128
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1LEADER:EXHAUSTED
