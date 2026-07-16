# PlusPerTrooper
#// TWI_122 Squad Support (Upgrade, Command, cost 3) — "Attach to a non-leader unit. Attached unit gains:
#// 'This unit gets +1/+1 for each Trooper unit you control.'" Host SOR_095 (Trooper) with 2 Battle Droid
#// tokens (Troopers) → 3 Troopers controlled → host gets +3/+3 → 6/6.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArena: TWI_T01:1:0
WithP1GroundArenaUpgrade: 0:TWI_122

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
