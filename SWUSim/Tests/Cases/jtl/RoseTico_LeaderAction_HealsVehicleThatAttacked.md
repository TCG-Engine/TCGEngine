# JTL_004 Rose Tico (leader) — Action [Exhaust]: Heal 2 damage from a Vehicle unit that attacked this
# phase. P1's damaged X-Wing (SOR_237, 2 damage) attacks P2's base this phase (dealing 2 and marking
# itself as having attacked), then Rose's leader action heals 2 from it (the only Vehicle that attacked).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:2
P1LEADER:EXHAUSTED
