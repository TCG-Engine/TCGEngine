# TS26_014 Yoda (Unit 4/4, cost 5) — When Played/When Defeated: create a Clone Trooper token and give it
# Sentinel for this phase. Playing Yoda creates a Clone (TS26_T02) with Sentinel.
## GIVEN
CommonSetup: bgw/rrk/{myResources:5;handCardIds:TS26_014}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TS26_T02
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
