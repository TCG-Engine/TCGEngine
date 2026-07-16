# Deal2AndForce
#// LOF_041 Drain Essence — "Deal 2 damage to a unit. The Force is with you." With one enemy unit the
#// deal-2 auto-resolves onto it, and P1 gains the Force.

## GIVEN
CommonSetup: bbk/rrk/{myResources:2;handCardIds:LOF_041}
P1OnlyActions: true
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASFORCE
P2GROUNDARENAUNIT:0:DAMAGE:2
