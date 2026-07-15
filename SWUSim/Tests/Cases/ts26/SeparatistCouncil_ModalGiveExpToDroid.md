# TS26_049 Separatist Council — "GiveExp" mode gives 2 Experience tokens to a Battle Droid token. The
# existing Battle Droid token (TS26_T01, 1/1) becomes 3/3 (auto-resolved as the only Battle Droid).
## GIVEN
CommonSetup: ggk/rrk/{myResources:4;handCardIds:TS26_049}
WithP1GroundArena: TS26_T01:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:GiveExp
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TS26_T01
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
