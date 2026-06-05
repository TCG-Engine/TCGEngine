# SOR_110 Frontline Shuttle — because the granted attack "can't attack bases," the action
# has no legal effect when the enemy has no units to attack (only a base). It is then a full
# no-op: the Shuttle is NOT defeated (cost unpaid), the friendly unit is unchanged, and no
# decision is pending. Guards the availability gate (a base is never a valid target here).

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1SpaceArena: SOR_110:1:0     # Frontline Shuttle (ready) — index 0
WithP1GroundArena: SOR_095:0:0    # Battlefield Marine (exhausted) — a would-be attacker
# P2 has no arena units — only a base, which can't be attacked by this action.

## WHEN
- P1>UseUnitAbility:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1NODECISION
