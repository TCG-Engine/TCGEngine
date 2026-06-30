# JTL_200 Shuttle Tydirium — if the milled card has an even cost, no Experience is offered. Deck top
# SOR_095 (cost 2, even) is milled; no decision follows.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_200:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P2BASEDMG:2
P1NODECISION
