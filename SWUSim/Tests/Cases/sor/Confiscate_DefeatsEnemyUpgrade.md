# SOR_251 Confiscate — defeat upgrade on enemy unit (auto-resolve)
# Single upgraded unit → no choice needed; upgrade goes to P2's discard.

## GIVEN
CommonSetup: grw/grw/{myResources:1;handCardIds:SOR_251}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:LOF_215

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P1RESAVAILABLE:0
