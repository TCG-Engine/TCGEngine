# LAW_204 Every Day, More Lies (Aggression event, cost 1) — "Each player discards a card from their
# hand." Caster has one extra card (auto-discards it); the opponent has two (real choose -> answers).

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
WithActivePlayer: 1
WithP1Hand: LAW_204
WithP1Hand: SEC_080
WithP2Hand: SOR_095
WithP2Hand: SOR_237

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myHand-0

## EXPECT
P1HANDCOUNT:0
P2HANDCOUNT:1
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
