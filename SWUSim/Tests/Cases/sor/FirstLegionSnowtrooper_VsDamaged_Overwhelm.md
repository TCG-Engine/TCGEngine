# SOR_130 First Legion Snowtrooper (2/3) — the Overwhelm half. Attacking a DAMAGED
# low-HP enemy: P2's Battlefield Marine (SOR_095, 3/3) starts with 2 damage. The
# Snowtrooper attacks at 2+2 = 4 power → Marine's damage becomes 2+4 = 6 vs printed
# HP 3 → defeated with 3 excess, which Overwhelm spills to P2's base (3 damage).
# (Both units die in the exchange; base takes the overflow.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0      # First Legion Snowtrooper (2/3)
WithP2GroundArena: SEC_080:1:2      # Battlefield Marine (3/3) with 2 damage

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:0
