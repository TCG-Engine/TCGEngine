# SEC_061 Willrow Hood — only ENEMY card abilities are blocked. The controller's OWN abilities can still
#   defeat the upgrade. P2 controls Willrow + 1 upgrade and plays its OWN Confiscate on it → the upgrade
#   is defeated normally (actor == controller, so the protection does not apply).

## GIVEN
CommonSetup: grw/grw/{theirResources:1;theirHandCardIds:SOR_251}
WithActivePlayer: 2
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P2>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
