# SHD_172 Krayt Dragon — it triggers only on an OPPONENT's play. P1 controls Krayt and plays SEC_080
# themselves → Krayt does NOT trigger (no damage, no decision).

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;myhandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SHD_172:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
