# SEC_008 Bail Organa (deployed) — When you play a card from your resources: Heal 1 damage from your base.
# P1's base starts with 2 damage; P1 smuggles SHD_065 (Vigilance, covered by the JTL_019 base) from
# resources → the deployed SEC_008 heals 1 from P1's base (2 → 1).

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008:1:1:1;
  myBase:JTL_019;
  myBaseDamage:2;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:1,8:SOR_095:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1BASEDMG:1
