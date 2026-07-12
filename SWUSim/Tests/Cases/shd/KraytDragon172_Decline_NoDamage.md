# SHD_172 Krayt Dragon — it's a "may": declining deals no damage.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
