# AnotherResistance_Sentinel
#// JTL_104 Raddus — While you control another Resistance card, this unit gains Sentinel. With another
#// Resistance unit (JTL_099) in play, Raddus has Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1GroundArena: JTL_099:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenDefeated_DealPower
#// JTL_104 Raddus — When Defeated: Deal damage equal to this unit's power to an enemy unit. Raddus (8/6,
#// pre-damaged to 1 remaining, no other Resistance so no Sentinel) attacks SOR_225 and is defeated by the
#// counter; its When Defeated deals 8 to the only remaining enemy unit SOR_046 (defeating it).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:5
WithP2SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NoOtherResistance_NoSentinel
#// JTL_104 Raddus — the Sentinel is conditional on controlling ANOTHER Resistance card. Alone (its only
#// friendly is a non-Resistance SOR_095), Raddus does NOT have Sentinel.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_104:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_104
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel
