# WhenDefeated_CreateTIE
#// JTL_087 TIE Ambush Squadron — When Defeated: Create a TIE Fighter token. The pre-damaged squadron dies
#// attacking SOR_044 and leaves a TIE Fighter behind.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:1

---

# WhenPlayed_CreateTIE
#// JTL_087 TIE Ambush Squadron — When Played: Create a TIE Fighter token. Playing it leaves the squadron
#// plus one TIE Fighter in the space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_087
WithP1Resources: 8

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:2
