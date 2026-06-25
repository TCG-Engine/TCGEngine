# LAW_014 Enfys Nest (deployed) — "Use this ability only once each round."
# Two IBH_006 Y-Wings each attack P2's base in space. The FIRST On Attack is reused
# (1 + 1 + combat 2 = 4); the SECOND attack's On Attack gets NO reuse offer this round
# (1 + combat 2 = 3). Total P2 base damage = 7, and the second attack auto-completes
# with no dangling decision.

## GIVEN
CommonSetup: brw/bbk/{
  myLeader:LAW_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: IBH_006:1:0
WithP1SpaceArena: IBH_006:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES
- P1>AttackSpaceArena:1:BASE

## EXPECT
P2BASEDMG:7
P1NODECISION
