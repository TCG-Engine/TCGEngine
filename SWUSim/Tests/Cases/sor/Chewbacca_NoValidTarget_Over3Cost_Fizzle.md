# SOR_003 Chewbacca — the action only plays a unit costing 3 or LESS. The hand holds SOR_046
# Consular Security Force (Vigilance,Heroism, cost 4 — both aspects covered by Chewbacca, so it stays
# 4), which is over the limit. P1 has 4 ready resources (enough to PAY for it), proving the gate is
# the ≤3 cost ceiling, not affordability: no valid target → the action fizzles. Chewbacca still
# exhausts (the action was used), the Security Force stays in hand, and no decision is pending.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
