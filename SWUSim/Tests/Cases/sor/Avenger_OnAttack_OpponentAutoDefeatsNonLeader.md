# SOR_040 Avenger (8/8 Space) — "When Played/On Attack: An opponent chooses a non-leader unit they
# control. Defeat that unit." Here the On Attack window: Avenger attacks the base; the opponent has a
# single non-leader unit (SEC_080), so the forced choice defeats it directly (no decision), then the
# 8 combat damage lands on the base.

## GIVEN
CommonSetup: bbk/brw/{
  theirLeader:SOR_014:1:1:1;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_040:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:1
P2BASEDMG:8
P1SPACEARENAUNIT:0:EXHAUSTED
