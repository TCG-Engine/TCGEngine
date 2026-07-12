# TWI_078 The Invasion of Christophsis (Event, cost 15) — "Exploit 4. Choose an opponent. Defeat each
# unit that player controls." P1 has no units (Exploit auto-skips); P2 controls two units, both defeated.

## GIVEN
CommonSetup: bbk/grw/{myResources:15;handCardIds:TWI_078}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
