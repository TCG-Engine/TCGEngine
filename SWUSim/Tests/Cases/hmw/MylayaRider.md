# BeastAndHealTwoFromYourBase
#// COVERAGE: offer=N/A (STRUCTURAL: nothing chosen) · decline=N/A (STRUCTURAL: mandatory)
#//           boundary=HealClampsAtZero · negative=OpponentsBaseIsNotHealed
#//           control=N/A ("your base" is the controller's) · reqboundary=N/A (STRUCTURAL: no decision)
#//           modes=2P only ("your base" is self-only) — TeamSuns_TeammatesBaseIsNotHealed pins it
#//
#// HMW_262 Mylaya Rider — Unit (Ground) 4/4, cost 6, [Heroism], Creature/Wookiee.
#// "When Played: Create a Beast token and heal 2 damage from your base."

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1BASEDMG:3

---

# HealClampsAtZero

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;myBaseDamage:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0

---

# UndamagedBase_BeastStillCreated

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:2

---

# OpponentsBaseIsNotHealed

## GIVEN
CommonSetup: yyw/yyw/{myResources:6;theirBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5

---

# TeamSuns_TeammatesBaseIsNotHealed

## GIVEN
CommonSetup: yyw/bbk/{myResources:6;myBaseDamage:5}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:5
WithP4Base: SOR_019:0
WithP1Hand: HMW_262

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:3
P3BASEDMG:5
