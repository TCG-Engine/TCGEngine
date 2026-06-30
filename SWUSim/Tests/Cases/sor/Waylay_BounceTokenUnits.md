# SOR_222 Waylay — bouncing token units sets them aside (not returned to hand or discard)
# P2 has 3 ground tokens and 2 space tokens. P1 bounces all 5.
# Expected: all tokens set aside, P2 hand/discard empty, P1 discard has 5 Waylays.

## GIVEN
CommonSetup: ybk/grw/{myResources:15;handCardIds:SOR_222,SOR_222,SOR_222,SOR_222,SOR_222}
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: TWI_T02:1:0
WithP2GroundArena: SEC_T01:1:0
WithP2SpaceArena: JTL_T01:1:0
WithP2SpaceArena: JTL_T02:1:0
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>PlayHand:0
- P1>ChooseTheirSpaceUnit:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:5
