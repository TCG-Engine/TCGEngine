# WhenPlayed_ExpEachOther
#// SEC_119 Crucible (Ground, 5/5) — When Played/When Defeated: give an Experience token to each OTHER
#//   friendly unit. Crucible is a SPACE unit; the two ground fillers each get +1/+1.

## GIVEN
CommonSetup: ggk/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_119

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_119
P1NODECISION

---

# WhenDefeated_ExpEachOther
#// SEC_119 Crucible — the same "give an Experience token to each OTHER friendly unit" also fires When
#//   Defeated. P2 plays Power of the Dark Side (SOR_041, "an opponent chooses a unit they control; defeat
#//   it"); P1 chooses Crucible. As it is defeated, each of P1's two other friendly units gains an Experience
#//   token (+1/+1); Crucible leaves play.

## GIVEN
CommonSetup: ggk/bbk
WithActivePlayer: 2
WithP1SpaceArena: SEC_119:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP2Resources: 3
WithP2Hand: SOR_041

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION
P2NODECISION

---

# WhenDefeated_UnderEnemyControl_TheNewControllersUnitsGetTheExperience
#// SEC_119 Crucible — "each OTHER FRIENDLY unit" is read from whoever controls Crucible when it is
#// defeated. P2 plays JTL_043 No Glory, Only Results on it, so at the moment of defeat P2's units are
#// the friendly ones: P2's SOR_095 gains the Experience token and P1's does not.

## GIVEN
CommonSetup: ggk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1SpaceArena: SEC_119:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
