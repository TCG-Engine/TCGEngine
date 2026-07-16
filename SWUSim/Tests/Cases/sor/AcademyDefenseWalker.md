# ExpToDamaged
#// SOR_037 Academy Defense Walker (5/5) — When Played: give an Experience token to
#// each friendly DAMAGED unit. P1's damaged Battlefield Marine (SOR_095, 1 damage) gets
#// +1/+1 (power 3 → 4); the undamaged Consular Security Force (SOR_046) gets nothing.

## GIVEN
CommonSetup: bbk/bbk/{myResources:6;handCardIds:SOR_037}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:1    # damaged → gets Experience — index 0
WithP1GroundArena: SOR_046:1:0    # undamaged → no Experience — index 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:1:POWER:3
