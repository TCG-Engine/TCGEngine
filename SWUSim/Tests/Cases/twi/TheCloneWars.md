# PayTwo_TokensBothSides
#// TWI_125 The Clone Wars (Event, cost 2, Command) — "Pay any number of resources. Create that many
#// Clone Trooper tokens. Each opponent creates that many Battle Droid tokens." Pay 2 → the caster gets
#// 2 Clone Troopers, and the opponent creates 2 Battle Droids. Cost 2 + pay 2 → needs 4 ready resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:TWI_125}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_T02
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T01
P1RESAVAILABLE:0

---

# PayZero_NoTokens
#// TWI_125 The Clone Wars — offered the NUMBERCHOOSE (1 ready resource left after paying cost 2), the
#// caster pays 0 → 0 tokens created on both sides, and the spare resource stays ready.

## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_125}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:1
P1NODECISION
