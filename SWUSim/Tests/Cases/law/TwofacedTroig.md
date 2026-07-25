# GiveToOpponentCredits
#// LAW_092 Two-Faced Troig (2/4, Sentinel) — When Played: you may have an opponent take control of this
#// unit. If you do, create 2 Credit tokens. Choose YES -> P2 controls it, P1 gets 2 Credits.

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

---

# PassKeepControl
#// LAW_092 Two-Faced Troig — When Played "you may have an opponent take control...". Decline (PASS): no
#// Credit tokens are created and P1 keeps control of the unit.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_092
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0
