# StatPerResourceAndSentinel
#// TS26_50 General Grievous (Unit 0/0, cost 5) — +1/+1 per resource you control; while undamaged he
#// gains Sentinel. With 3 resources he is 3/3; undamaged → Sentinel; the damaged copy loses Sentinel.
## GIVEN
CommonSetup: ggk/ggk
WithP1Resources: 3
WithP1GroundArena: [TS26_50:1:0 TS26_50:1:2]
P1OnlyActions: true
## WHEN
- P1>Pass
## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:1:NOTKEYWORD:Sentinel

---

# LosingHisAbilityDefeatsHimImmediately
#// TS26_50 General Grievous — his printed body is 0/0 and all of it comes from "+1/+1 for each resource
#// you control". Attaching SEC_054 Exiled from the Force ("attached unit loses all abilities except Grit")
#// removes that, so he is a 0-HP unit and is defeated on the spot, emptying the arena.

## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SEC_054
WithP1GroundArena: TS26_50:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
