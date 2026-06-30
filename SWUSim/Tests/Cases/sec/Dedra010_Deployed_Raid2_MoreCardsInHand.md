# SEC_010 Dedra Meero (deployed) — While you have more cards in hand than an opponent, this unit gains
# Raid 2 (+2/+0 while attacking). P1 has 2 cards, P2 has 0 → Raid 2 active. SEC_010 (2/5) attacks the
# enemy base for 2 + 2 = 4.

## GIVEN
CommonSetup: brk/bbk/{
  myLeader:SEC_010:1:1:1;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
