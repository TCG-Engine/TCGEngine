# SOR_251 Confiscate — can target own unit's upgrade
# Only P1's unit has an upgrade → auto-selects it; upgrade goes to P1's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:LOF_215
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:2
P1RESAVAILABLE:0
