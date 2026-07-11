# SHD_172 Krayt Dragon — Krayt is NOT unique, so a player can control two. When P2 plays SEC_080 (printed
# 2), BOTH of P1's Krayts trigger; P1 resolves them one at a time (a single collapsed trigger loops the
# may-deal per Krayt — this avoids the pre-existing engine hang on two IDENTICAL reactive triggers). P1
# sends both to P2's base → 2 + 2 = 4 base damage.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SHD_172:1:0 SHD_172:1:0]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
