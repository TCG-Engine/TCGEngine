# JTL_033 Onyx Squadron Brute — When Defeated: Heal 2 damage from a base. JTL_033 (2/3, pre-damaged to
# 1 remaining HP) attacks SOR_225 and is defeated by the counter; its When Defeated heals 2 from P1's
# base (3 → 1 damage).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  myBaseDamage:3;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_033:1:2
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1BASEDMG:1
