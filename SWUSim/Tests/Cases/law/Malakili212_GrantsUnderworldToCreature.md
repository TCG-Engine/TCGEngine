# LAW_212 Malakili (2/4) — "Each friendly Creature unit ... gains the Underworld trait." SOR_164 is a
# Creature but NOT natively Underworld; with Malakili in play it counts as Underworld, so LAW_249 Black
# Sun Cabalist (When Played: give an Experience token to another friendly Underworld unit) can target
# it. Choosing SOR_164 (the granted unit) makes it 5/6. Without the grant SOR_164 wouldn't be a legal
# target and the choice would auto-resolve to Malakili instead — so SOR_164 ending at 5/6 proves the
# grant works.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: LAW_212:1:0
WithP1GroundArena: SOR_164:1:0
WithP1Hand: LAW_249

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_164
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:6
