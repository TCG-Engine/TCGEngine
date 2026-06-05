# SOR_091 The Emperor's Legion — gating guard: a unit sitting in your discard that was NOT defeated
# THIS PHASE (seeded there) is NOT returned. With nothing defeated this phase, SOR_091 returns
# nothing → the seeded SOR_128 stays in discard, hand stays empty (only the event resolves to discard).

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091;discardCardIds:SOR_128}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
