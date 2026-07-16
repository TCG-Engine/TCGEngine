# ExhaustShieldExp
#// LOF_054 Calm in the Storm — Exhaust a friendly unit; if you do, give it a Shield + 2 Experience tokens.
#// Plo Koon (6/8) is exhausted, gains a Shield and 2 Experience → 8/10.

## GIVEN
CommonSetup: bbw/ggk/{myResources:2;handCardIds:LOF_054}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:10
