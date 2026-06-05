# SOR_199 Bamboozle — unplayable: 1 resource, no other Cunning card in hand
# Alternate cost condition not met (no Cunning card to discard). Normal cost (2)
# cannot be paid. Card stays in hand; no effect fires.

## GIVEN
CommonSetup: ygw/grw/{myResources:1;handCardIds:SOR_199}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:READY
