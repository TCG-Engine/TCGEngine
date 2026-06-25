# SOR_133 Seventh Sister — base-damage rider with NO enemy ground unit to target. The "may deal 3
# to a ground unit" has zero legal targets → SWUQueueMayChooseTarget no-ops (no dangling decision,
# no crash). Base still takes her 3 combat damage; P1 keeps a clean turn (no pending decision).

## GIVEN
CommonSetup: rrk/brw/{
  myLeader:SOR_011;
  myBase:SOR_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_133:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1NODECISION
P1GROUNDARENACOUNT:1
