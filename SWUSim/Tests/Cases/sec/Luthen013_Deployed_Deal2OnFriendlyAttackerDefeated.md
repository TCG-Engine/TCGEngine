# SEC_013 Luthen Rael (deployed) — "When a friendly unit is defeated while attacking: You may deal 2
# damage to a unit or base." P1's SOR_128 (idx 1) attacks SOR_063 (Sentinel) and dies; the deployed
# Luthen reacts → deal 2 to the enemy base. (No leader-exhaust cost on the deployed side.)

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:SEC_013:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:1
