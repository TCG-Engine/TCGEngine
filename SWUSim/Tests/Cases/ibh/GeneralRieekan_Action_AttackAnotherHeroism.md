# IBH_023 General Rieekan (Ground, 2/6, Command/Heroism) — Action [Exhaust]: attack with another Heroism
#   unit; it gets +2/+0 for this attack. P1's Heroism 3/3 attacks the enemy base for 3+2 = 5. Rieekan
#   exhausts to pay; the attacker exhausts from attacking.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: IBH_023:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
