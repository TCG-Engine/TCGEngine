# SOR_203 Cunning — Opponent discards a random card + Return a ≤4-power non-leader unit to hand. P1
# resolves Discard first (P2 holds exactly 1 card → deterministic), then ReturnUnit bounces SOR_128 (3
# power) to P2's hand. P2 ends with 1 card in hand (the bounced unit) and 1 in discard.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_203
WithP1Resources: 8
WithP2Hand: SOR_095
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Discard
- P1>AnswerDecision:ReturnUnit

## EXPECT
P2HANDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
