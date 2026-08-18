# WhenPlayedOpponentChoosesCreditReady
#// LAW_080 Luke Skywalker — the opponent instead picks "create a Credit token; ready this unit". P2
#// gains a Credit; Luke (entered exhausted) becomes ready.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7;theirResources:0}
WithActivePlayer: 1
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:CreditAndReady

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_080
P1GROUNDARENAUNIT:0:READY
P2CREDITCOUNT:1

---

# WhenPlayedOpponentChoosesDeal5
#// LAW_080 Luke Skywalker (9/7) — When Played: an opponent chooses one: [create a Credit token; ready
#// this unit] OR [you may deal 5 to a unit]. The opponent picks Deal5 -> P1 deals 5 to the enemy SOR_046.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Deal5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WhenPlayedOpponentChoosesDeal5ButDeclines
#// LAW_080 Luke Skywalker — the opponent picks the "you may deal 5" option, but the deal is optional
#// ("you may"), so P1 declines. No damage is dealt, no Credit is created, and Luke stays exhausted.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Deal5
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_080
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0

---

# TheMODECHOICEIsRaisedOnTHEOPPONENTSQueue
#// LAW_080 Luke Skywalker — "An OPPONENT chooses one" puts the mode decision on the opposing seat. All
#// three existing sections ANSWER it as P2 without ever asserting whose decision it was, so a choice
#// wrongly raised on the caster's queue would satisfy them all. Here the decision is left pending after
#// P1's play and read from P2's side, with both modes on offer.

## GIVEN
CommonSetup: ryw/bgw/{myResources:7}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_080

## WHEN
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P2OPTIONHAS:CreditAndReady
P2OPTIONHAS:Deal5
