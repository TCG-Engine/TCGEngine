# GrantsGrit
#// LAW_128 Veiled Strength (Upgrade, +0/+0) — "Attached unit gains Grit." SEC_080 (3/3) with 2 damage
#// and Veiled Strength gains Grit (+1/+0 per damage) → power 3+2 = 5. Without the grant it would be 3.

## GIVEN
CommonSetup: bbw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:2
WithP1GroundArenaUpgrade: 0:LAW_128

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5
