# SEC_178 Pursue the Lead (Event, cost 2) — "Choose a player. That player discards a card from their
#   hand. If it costs 3 or less, create a Spy token." Choose Opponent; P2's only card SOR_095 (cost 2 ≤ 3)
#   is discarded → create a Spy.

## GIVEN
CommonSetup: rrk/grw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SEC_178
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2HANDCOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION
