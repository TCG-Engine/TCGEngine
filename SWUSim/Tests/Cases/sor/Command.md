# Modal_PowerStrikeAndExperience
#// SOR_107 Command (event, cost 4) — "Choose two." PowerStrike (a friendly unit deals its power to a
#// non-unique enemy unit): SEC_080 (3 power) deals 3 to LAW_124 (non-unique). Then Experience: give 2
#// Experience tokens to SEC_080 (UPGRADECOUNT 2).

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_107
WithP1Resources: 6
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PowerStrike
- P1>AnswerDecision:Experience
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1DISCARDCOUNT:1

---

# Modal_ResourceAndReturn
#// SOR_107 Command — Resource (put this event into play as a resource) + Return (return a unit from your
#// discard to hand). After playing SOR_107 the discard holds [SEC_080, SOR_107]; Resource moves SOR_107
#// to the resource row (count 6→7), Return moves SEC_080 to hand. Discard ends empty.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:SOR_107;discardCardIds:SEC_080}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Resource
- P1>AnswerDecision:Return

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
P1RESCOUNT:7
