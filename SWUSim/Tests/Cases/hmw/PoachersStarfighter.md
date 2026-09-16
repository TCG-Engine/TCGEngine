# Yes_DefeatsItself_BeastTakesOneDamage
#// COVERAGE: offer=N/A (STRUCTURAL: a YESNO on itself, no pool) · decline=No_KeepsTheStarfighter
#//           boundary=N/A (STRUCTURAL: no number) · control=N/A (the Starfighter is the caster's own)
#//           reqboundary=AcrossTheRequestBoundary · modes=2P only (no player reference)
#//
#// HMW_153 Poacher's Starfighter — Unit (Space) 3/1, cost 2, [Aggression][Villainy], Underworld/Vehicle/Fighter.
#// "When Played: You may defeat this unit. If you do, create a Beast token and deal 1 damage to it."

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_153

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# No_KeepsTheStarfighter

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_153

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1NODECISION

---

# OnlyTheNewBeastIsDamaged
#// An existing friendly ground unit is untouched — "it" is the created Beast.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_153
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_T03
P1GROUNDARENAUNIT:1:DAMAGE:1

---

# AcrossTheRequestBoundary

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_153

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:HMW_T03
P1GROUNDARENAUNIT:0:DAMAGE:1
