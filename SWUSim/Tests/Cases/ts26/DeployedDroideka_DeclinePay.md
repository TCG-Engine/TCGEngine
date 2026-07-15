# TS26_077 Deployed Droideka — declining the optional payment leaves it a plain 4/3 with no shield, and
# the 2 resources are kept (6 - 4 play = 2 left).
## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:TS26_077}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1RESAVAILABLE:2
