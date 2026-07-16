# GrantsRaid
#// JTL_211 Independent Smuggler (pilot) — Attached unit gains Raid 1. The host SOR_237 with the pilot has
#// the Raid keyword.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_211

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Raid
