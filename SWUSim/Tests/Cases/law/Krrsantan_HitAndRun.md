# ActionDiscardTwoBounce
#// LAW_084 Krrsantan (7/7, Ambush, Overwhelm) — Action [discard 2 cards from your hand]: return this
#// unit to your hand. Discard SEC_080 + SOR_237; Krrsantan returns to hand.

## GIVEN
CommonSetup: ryk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_084:1:0
WithP1Hand: SEC_080
WithP1Hand: SOR_237

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myHand-0&myHand-1

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1DISCARDCOUNT:2
