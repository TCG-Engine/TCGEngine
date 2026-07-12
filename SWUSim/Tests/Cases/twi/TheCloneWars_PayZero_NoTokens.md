# TWI_125 The Clone Wars — offered the NUMBERCHOOSE (1 ready resource left after paying cost 2), the
# caster pays 0 → 0 tokens created on both sides, and the spare resource stays ready.

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
