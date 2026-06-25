# JTL_187 Bossk — On Attack: Exhaust the defender and deal 1 damage to it (if a unit). Bossk attacks the
# ready SOR_046: it is exhausted and takes 1 (on attack) + 4 (combat) = 5 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_187:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:0:EXHAUSTED
