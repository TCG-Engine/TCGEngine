# ExpThreeOfficials
#// SEC_124 Budget Scheming (Event, Command, cost 2) — give an Experience token to each of up to 3 Official units.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP1GroundArena: SEC_041:1:0
WithP1Hand: SEC_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1NODECISION

---

# ExpUpTo3_EnemyOfficialEligible_NonOfficialExcluded
#// SEC_124 Budget Scheming — "up to 3 Official units" spans BOTH sides: friendly Officials
#//   (SOR_062 Regional Governor, SOR_109 Colonel Yularen ISB Director, SOR_129 Admiral Ozzel) AND the
#//   enemy Official (SOR_189 Leia Defiant Princess) are all selectable, while a non-Official friendly
#//   space unit (LOF_131 Strikeship) is not.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_062:1:0
WithP1GroundArena: SOR_109:1:0
WithP1GroundArena: SOR_129:1:0
WithP1SpaceArena: LOF_131:1:0
WithP2GroundArena: SOR_189:1:0
WithP1Hand: SEC_124

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&theirGroundArena-0

---

# ExpChoose2_OneEnemyOfficial
#// SEC_124 Budget Scheming — give Experience to a friendly Official (Regional Governor) AND an enemy
#//   Official (Leia). Each gains exactly one Experience upgrade; the non-chosen friendly Officials and
#//   the non-Official Strikeship stay bare.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_062:1:0
WithP1GroundArena: SOR_109:1:0
WithP1GroundArena: SOR_129:1:0
WithP1SpaceArena: LOF_131:1:0
WithP2GroundArena: SOR_189:1:0
WithP1Hand: SEC_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# ExpChooseNothing
#// SEC_124 Budget Scheming — "up to 3" allows choosing zero: declining gives no Experience to anyone.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_062:1:0
WithP1GroundArena: SOR_109:1:0
WithP2GroundArena: SOR_189:1:0
WithP1Hand: SEC_124

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION
