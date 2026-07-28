# PayBuff
#// LAW_148 Smuggler's YT-2400 (4/5, space, Ambush) — When Played: you may pay 1 resource. If you do,
#// this unit gets +1/+1 for this phase. No enemy (Ambush no trigger); pay 1 -> 5/6.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:6

---

# DeclinePayNoBuff
#// LAW_148 Smuggler's YT-2400 — the When Played pay is optional; declining leaves it a base 4/5 and
#// costs no extra resource. Play it (no enemy so Ambush is moot) and decline the pay.

## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
WithP1Hand: LAW_148

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENAUNIT:0:CARDID:LAW_148
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
