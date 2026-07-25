# PayShield
#// LAW_113 Shield Drive Outfitter (Vigilance, cost 1) — When Played: you may pay 1 resource. If you do,
#// give a Shield token to a unit. Pay 1; the only unit (itself) auto-targets and gains a Shield.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_113
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0

---

# DeclinePayNoShield
#// LAW_113 Shield Drive Outfitter — the When Played pay is optional; declining it gives no Shield and
#// costs no extra resource. Play it (cost 1), decline the pay: 1 resource used total, no Shield token.

## GIVEN
CommonSetup: bbw/bgw/{myResources:2}
WithP1Hand: LAW_113

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_113
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:1
