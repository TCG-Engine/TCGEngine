# SHD_172 Krayt Dragon — the opponent playing their OWN card doesn't trigger their Krayt. P2 controls
# Krayt; P1 passes and P2 plays SEC_080 → P2's Krayt does NOT trigger (its own play is not an opponent's).

## GIVEN
CommonSetup: rrk/rrk/{theirResources:6;theirHandCardIds:SEC_080}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP2GroundArena: SHD_172:1:0

## WHEN
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
