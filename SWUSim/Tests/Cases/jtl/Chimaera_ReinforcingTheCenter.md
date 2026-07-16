# UseAnotherUnitsWhenDefeated
#// JTL_039 Chimaera — When Played: may use a "When Defeated" ability on another friendly unit.
#// P1 plays Chimaera; chooses JTL_087 (alive, "When Defeated: create a TIE") to use its ability.
#// JTL_087 stays in play; a TIE token is created. Arena ends with JTL_087 + Chimaera + TIE = 3.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP1SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:3

---

# WhenDefeated_CreatesTwoTies
#// JTL_039 Chimaera — "When Defeated: Create 2 TIE Fighter tokens." Chimaera (5/6, pre-damaged to 1 HP)
#// attacks a small enemy space unit and dies to the counter; its When Defeated then makes 2 TIE tokens
#// (JTL_T01) for its controller. (Active player attacks into a lethal counter — the combat-WhenDefeated
#// pattern that doesn't stall the harness.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_039:1:5
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:JTL_T01
P1SPACEARENAUNIT:1:CARDID:JTL_T01

---

# CannotSelectItself
#// JTL_039 Chimaera — "use a When Defeated ability on ANOTHER friendly unit." With no other friendly unit

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039
P1NODECISION

---

# CannotTriggerEnemyWhenDefeated
#// JTL_039 Chimaera targets only FRIENDLY units. With the only When Defeated unit being an enemy (JTL_087,
#// "When Defeated: create a TIE"), Chimaera's When Played finds no valid target — no TIE is created and the
#// enemy unit is untouched.

## GIVEN
CommonSetup: ggk/bbk/{myLeader:JTL_005;myBase:JTL_022;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_039
WithP2SpaceArena: JTL_087:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_039
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:JTL_087
P1NODECISION
