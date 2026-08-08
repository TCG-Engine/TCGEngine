# PayXExperience
#// LOF_255 Curious Flock (1/1) — When Played: Pay up to 6 resources. For each resource paid, give an
#// Experience token to this unit. P1 pays 2 (then declines), so the Flock becomes 3/3 and 3 resources are
#// spent total (1 to play + 2 paid).

## GIVEN
CommonSetup: bbw/ggk/{myResources:7;handCardIds:LOF_255}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:4

---

# PayZero_NoExperience
#// LOF_255 Curious Flock — the pay-up-to-6 is optional; paying 0 gives no Experience. The Flock stays 1/1
#// and only its own 1-cost is spent (myResources:7 → 6 available). Intended: "should allow paying up to 6
#// resources and gain experience" (chooseListOption '0').

## GIVEN
CommonSetup: bbw/ggk/{myResources:7;handCardIds:LOF_255}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:6

---

# ResourceCapped_PayMax
#// LOF_255 Curious Flock — the amount payable is capped by available resources. With only 3 resources, the
#// Flock costs 1 leaving 2, so at most 2 Experience can be paid. Paying both makes it 3/3 with 0 resources
#// left. Intended: "should allow paying up to 6 resources (but max is 3)" (range 0-2).

## GIVEN
CommonSetup: bbw/ggk/{myResources:3;handCardIds:LOF_255}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:YES
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1RESAVAILABLE:0
