# TS26_049 Separatist Council (Unit 2/6, cost 4) — When Played: choose one. "CreateDroid" mode creates a
# Battle Droid token (TS26_T01).
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_049}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:CreateDroid
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T01
