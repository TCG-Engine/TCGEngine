# TWI_064 Ki-Adi-Mundi — with Coordinate INACTIVE (P1 controls only Ki-Adi + 1 unit = 2), the
# opponent's 2nd card does NOT trigger the reaction: no decision, no draw.

## GIVEN
CommonSetup: bbk/rrk/{theirResources:8}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: TWI_064:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2Hand: SEC_080
WithP2Hand: SEC_080
WithP1Deck: [SOR_095 SOR_046 SOR_128]

## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1DECKCOUNT:3
P1NODECISION
