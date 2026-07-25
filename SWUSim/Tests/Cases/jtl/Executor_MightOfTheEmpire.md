# WhenPlayed_CreateThreeTIEs
#// JTL_090 Executor — When Played: Create 3 TIE Fighter tokens. Playing it leaves the Executor plus three
#// TIE Fighters (4 units) in the space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_090
WithP1Resources: 15

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:4

---

# OnAttack_CreateThreeTIEs
#// JTL_090 Executor — On Attack: Create 3 TIE Fighter tokens. Seated ready (12/12), Executor attacks the
#// enemy base for 12; the On Attack trigger creates 3 TIE Fighters, leaving Executor + 3 TIEs = 4 units in
#// P1's space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_090:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:4
P1SPACEARENAUNIT:0:CARDID:JTL_090
P2BASEDMG:12

---

# WhenDefeated_CreateThreeTIEs
#// JTL_090 Executor — When Defeated: Create 3 TIE Fighter tokens. P2 plays Fell the Dragon (SHD_078,
#// "defeat a non-leader unit with 5 or more power") on the 12-power Executor. Executor is defeated to the
#// discard, and its When Defeated trigger still fires, leaving 3 TIE Fighters in P1's space arena.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: JTL_090:1:0
WithP2Hand: SHD_078
WithP2Resources: 5

## WHEN
- P2>PlayHand:0
- P1>Drain

## EXPECT
P1SPACEARENACOUNT:3
P1SPACEARENAUNIT:0:CARDID:JTL_T01
