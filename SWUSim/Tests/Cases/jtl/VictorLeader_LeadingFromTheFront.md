# Aura_OtherSpacePlusOne
#// JTL_085 Victor Leader — Each OTHER friendly space unit gets +1/+1. SOR_237 (2/3) becomes 3/4;
#// Victor Leader itself (2/4) is not buffed by its own aura.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_085:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_085
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:POWER:3
P1SPACEARENAUNIT:1:HP:4
