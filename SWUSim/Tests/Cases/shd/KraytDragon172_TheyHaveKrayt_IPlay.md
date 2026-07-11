# SHD_172 Krayt Dragon — cross-player direction: P2 controls Krayt, P1 (active) plays SEC_080 (printed 2).
# P2's Krayt triggers on P1's play → P2 may deal 2 to P1's base or a P1 ground unit. P2 picks P1's base
# (from P2's frame that's theirBase-0) → P1BASEDMG 2. Drives as one P1 action + a P2 reaction answer.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:EffectStack-0
- P2>AnswerDecision:theirBase-0

## EXPECT
P1BASEDMG:2
