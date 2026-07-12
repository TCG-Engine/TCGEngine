# SHD_172 Krayt Dragon (Unit, Ground, cost 9, 10/10, Overwhelm, Creature)
#   "When an opponent plays a card: You may deal damage equal to that card's cost to their base or a
#    ground unit they control."
# P1 controls Krayt. P1 passes; P2 plays SEC_080 (printed cost 2). Krayt triggers on the opponent's play →
# P1 may deal 2 (the printed cost) to P2's base or a P2 ground unit. P1 picks P2's base → P2BASEDMG 2.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
