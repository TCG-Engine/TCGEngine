# TWI_064 Ki-Adi-Mundi (Unit 5/7, Ground) — "Coordinate - When an opponent plays their SECOND card
# each phase: You may draw 2 cards." P1 controls Ki-Adi + 2 Clone tokens (Coordinate active). P2 plays
# two SEC_080 units; on the 2nd, P1's reaction fires. P1 accepts (draws 2 → deck 3→1, hand 0→2).
# The reactive orchestration needs the EffectStack-0 step before the YESNO (non-active-player reaction).

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
- P1>AnswerDecision:YES

## EXPECT
P1DECKCOUNT:1
P1HANDCOUNT:2
