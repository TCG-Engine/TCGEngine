# TS26_077 Deployed Droideka (Unit 4/3, cost 4) — Ambush. When Played: you may pay 2 resources; if you
# do, give an Experience token and a Shield token to this unit. Paying makes it 5/4 with a shield (6
# resources - 4 play - 2 pay = 0 left).
## GIVEN
CommonSetup: yyk/rrk/{myResources:6;handCardIds:TS26_077}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:0
