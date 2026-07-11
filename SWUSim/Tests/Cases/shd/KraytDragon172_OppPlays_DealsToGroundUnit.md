# SHD_172 Krayt Dragon — deal the damage to a ground unit the opponent controls (not the base).
# P2 plays SEC_080 (printed 2); P1's Krayt deals 2 to that just-played ground unit (survives, DAMAGE:2).

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:0
