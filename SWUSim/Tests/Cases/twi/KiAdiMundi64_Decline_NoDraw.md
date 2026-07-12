# TWI_064 Ki-Adi-Mundi — the "you may draw 2" is optional: declining (NO) draws nothing (deck stays 3).

## GIVEN
CommonSetup: bbk/rrk/{theirResources:8}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: TWI_064:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2Hand: SEC_080
WithP2Hand: SEC_080
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:NO

## EXPECT
P1DECKCOUNT:3
P1HANDCOUNT:0
