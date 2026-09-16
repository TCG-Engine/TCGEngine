# TwoBeasts_OneOfThemReady
#// COVERAGE: offer=N/A (STRUCTURAL: the two tokens are identical — which one readies is not a real choice)
#//           decline=N/A (STRUCTURAL: mandatory) · boundary=N/A (STRUCTURAL: fixed quantity)
#//           control=N/A · reqboundary=N/A (STRUCTURAL: no decision)
#//           negative=OtherExhaustedUnitsStayExhausted · modes=2P only (no player reference)
#//
#// HMW_195 Catch the Scent — Event, cost 5, [Aggression], Innate.
#// "Create 2 Beast tokens and ready 1 of them."

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_195

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# TheReadyBeastCanAttackAtOnce

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_195

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# OtherExhaustedUnitsStayExhausted

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_195
WithP1GroundArena: SOR_095:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# BeastsAreThreeByThreeCreatures

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_195

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:3
