# IBH_001 Leia Organa (deployed) — On Attack: heal 1 from a friendly unit and 1 from another friendly
#   unit. Leia deploys (5 resources), attacks the base; two damaged space units (2 dmg each) each heal 1.

## GIVEN
CommonSetup: ggw/brk/{
  myLeader:IBH_001
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:2
WithP1SpaceArena: SOR_237:1:2

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:1:DAMAGE:1
P2BASEDMG:3
