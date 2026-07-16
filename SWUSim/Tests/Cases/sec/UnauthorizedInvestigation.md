# Decline_OneSpy
#// SEC_181 Unauthorized Investigation — decline the disclose → only the first Spy token is created.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_181
WithP1Hand: SEC_133

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION

---

# Disclose_SecondSpy
#// SEC_181 Unauthorized Investigation (Event, cost 3, Aggression) — "Create a Spy token. You may
#//   disclose Aggression → create another Spy token." Play → 1 Spy; disclose SEC_133 (Aggression) → 2nd Spy.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SEC_181
WithP1Hand: SEC_133

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1NODECISION
