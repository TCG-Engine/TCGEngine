# ASH_005 Luke Skywalker (DEPLOYED unit side) — clean fizzle. Deployed Luke attacks the enemy base (no
# counter → 0 damage on him) with an undamaged P1 base, so neither valid heal source has any damage. The
# mandatory heal has no beneficial target → no decision is queued (no crash, no dangling prompt).
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:6
