# SEC_011 Governor Pryce (leader) — Action [1 resource, Exhaust]: Ready a token unit. P1's exhausted
# Battle Droid token (TWI_T01) is readied.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_011;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1GroundArena: TWI_T01:0:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_T01
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
