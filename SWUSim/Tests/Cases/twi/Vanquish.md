# DefeatNonLeaderUnit
#// TWI_077 Vanquish (Event, cost 5, Vigilance, Tactic) — "Defeat a non-leader unit." The lone enemy
#// SOR_046 is defeated.

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:TWI_077}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
