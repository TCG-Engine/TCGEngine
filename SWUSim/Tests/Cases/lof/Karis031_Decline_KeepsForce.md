# LOF_031 Karis — decline branch: when defeated, P1 declines to use the Force, so the Force token is
# kept and the 4/7 is not debuffed (power stays 4).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_031:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:POWER:4
