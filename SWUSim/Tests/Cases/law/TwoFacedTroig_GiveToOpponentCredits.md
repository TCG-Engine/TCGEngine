# LAW_092 Two-Faced Troig (2/4, Sentinel) — When Played: you may have an opponent take control of this
# unit. If you do, create 2 Credit tokens. Choose YES -> P2 controls it, P1 gets 2 Credits.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_092
P1CREDITCOUNT:2
