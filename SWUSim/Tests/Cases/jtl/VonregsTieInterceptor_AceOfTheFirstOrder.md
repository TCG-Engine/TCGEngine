# Power4_Overwhelm
#// JTL_137 Vonreg's TIE Interceptor — While it has 4+ power it gains Overwhelm; while it has 6+ power it
#// gains Raid 1. With one Experience token (3 → 4 power) it has Overwhelm but not yet Raid.

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

---

# Power6_OverwhelmRaid
#// JTL_137 Vonreg's TIE Interceptor — with Academy Training (+2/+2) and an Experience token (3+2+1=6
#// power) it has both Overwhelm (4+) and Raid (6+).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_137:1:0
WithP1SpaceArenaUpgrade: 0:SOR_120
WithP1SpaceArenaUpgrade: 0:SOR_T01

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:POWER:6
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
