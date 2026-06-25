# SEC_005 Satine Kryze (leader) — with only 1 healable damage (unit has 1 damage), the max heal is 1, so
# there is no amount choice: it heals 1 and deals 1 to your base automatically. Proves the maxHeal==1
# auto path (no OPTIONCHOOSE).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SEC_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:1
P1LEADER:EXHAUSTED
P1NODECISION
