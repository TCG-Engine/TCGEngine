# JTL_137 Vonreg's TIE Interceptor — While it has 4+ power it gains Overwhelm; while it has 6+ power it
# gains Raid 1. With one Experience token (3 → 4 power) it has Overwhelm but not yet Raid.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_137:1:0
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_137
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid
