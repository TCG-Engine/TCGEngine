# ReturnUnit_TwoSpy
#// SEC_105 Renewed Friendship (Event, cost 4, Command/Heroism) — "Return a unit from your discard pile
#//   to your hand. Create 2 Spy tokens." Discard holds SEC_097 (a unit); return it → hand, then 2 Spy.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;discardCardIds:SEC_097}
P1OnlyActions: true
WithP1Hand: SEC_105

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1HANDCOUNT:1
P1NODECISION

---

# NoUnitToReturn_StillTwoSpy
#// SEC_105 Renewed Friendship — "Return a unit from your discard pile to your hand. Create 2 Spy
#//   tokens." When the discard holds no unit (only an event, SOR_041), the return has no legal
#//   target and is skipped, but the 2 Spy tokens are still created. The event stays in discard.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;discardCardIds:SOR_041}
P1OnlyActions: true
WithP1Hand: SEC_105

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_T01
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION
