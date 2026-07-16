# NoToken_NoRaid
#// JTL_081 First Order TIE Fighter — without a token unit in play, it does not have Raid.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_081:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:NOTKEYWORD:Raid

---

# TokenControl_Raid
#// JTL_081 First Order TIE Fighter — While you control a token unit, this unit gains Raid 1. With a TIE
#// Fighter token (JTL_T01) in play, JTL_081 has Raid.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_081:1:0
WithP1SpaceArena: JTL_T01:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_081
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
