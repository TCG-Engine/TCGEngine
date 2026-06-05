# TWI_137 Savage Opress WhenPlayed — does NOT ready self when unit counts are equal.
# P2 has 1 unit; after playing Savage, P1 also has 1 unit. Condition not met; Savage stays exhausted.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
