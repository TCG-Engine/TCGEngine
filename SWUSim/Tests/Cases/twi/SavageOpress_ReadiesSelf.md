# TWI_137 Savage Opress WhenPlayed — readies self when P1 has fewer units than P2.
# P2 has 2 units; after playing Savage, P1 has 1 unit < P2's 2. Trigger fires; Savage is readied.
# Units enter play exhausted (Status=0); the self-ready makes Savage ready.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY
