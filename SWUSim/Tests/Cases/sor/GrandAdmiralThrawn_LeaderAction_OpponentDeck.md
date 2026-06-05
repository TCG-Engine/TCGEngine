# SOR_016 Grand Admiral Thrawn — Leader Action: choose opponent's deck (top = SOR_095, cost 2).
# Same effect as own deck but cost derived from opponent's top card.

## GIVEN
CommonSetup: yyk/grw/{myResources:1}
P1OnlyActions: true
WithP2Deck: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1LEADER:EXHAUSTED
P1RESCOUNT:1
P1RESAVAILABLE:0
