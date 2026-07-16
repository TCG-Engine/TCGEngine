# ImmuneToEnemyBounce
#// TWI_220 Shadowed Intentions (Upgrade, cost 3) — "Attached unit gains: 'This unit can't be captured,
#// defeated, or returned to its owner's hand by enemy card abilities.'" P2's Waylay (TWI_226, "Return a
#// non-leader unit to its owner's hand") cannot return the TWI_220-protected SOR_095, which stays in play.

## GIVEN
CommonSetup: rrk/yyk/{theirResources:3;theirhandCardIds:TWI_226}
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:TWI_220

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
