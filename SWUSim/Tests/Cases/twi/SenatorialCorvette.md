# WhenDefeated_OppDiscards
#// TWI_148 Senatorial Corvette (Unit 5/4, Space, cost 5, Republic/Vehicle/Capital Ship) — Saboteur + "When
#// Defeated: Each opponent discards a card from their hand." It attacks JTL_069 (4/7) and dies to the 4
#// counter-damage; P2 then discards their only card.

## GIVEN
CommonSetup: rrk/bbw/{theirhandCardIds:SOR_095}
P1OnlyActions: true
WithP1SpaceArena: TWI_148:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P2HANDCOUNT:0
