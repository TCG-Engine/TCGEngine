# Krennic leader unit side has Restore 2.
# P1 base starts at 4 damage. Krennic (power 2) attacks P2 base,
# heals 2 from P1 base -> P1 base at 2; P2 base takes 2.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001:1:1:1;
  myBaseDamage:4
}
SkipPreGame: true

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:2
