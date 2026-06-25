# ASH_009 Ahsoka Tano (deployed, 5 power) — On Attack: may give a unit with less power than
# this unit +2/+0 for this phase. The X-Wing (power 2 < 5) qualifies → power 4.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:ASH_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
