# ExpToUpTo3Units
#// TS26_60 Take Charge (Event, cost 3, Command) — Give an Experience token to each of up to 3 units.
#// Two units are chosen; each gains 1 Experience (+1/+1).
## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_60}
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4

---

# ChoosingFewerThanThreeUnits
#// TS26_60 Take Charge — "each of UP TO 3 units". Naming only SEC_080 gives it the Experience (3 -> 4
#// power) and leaves SOR_095 at its printed 3.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_60}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3

---

# TheFullThreeTargets
#// TS26_60 Take Charge — the upper bound of "up to 3 units". All three friendly units are named and each
#// gains an Experience token, taking every one of them to 4 power.

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:TS26_60}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0 SOR_046:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:4
P1GROUNDARENAUNIT:2:POWER:4
