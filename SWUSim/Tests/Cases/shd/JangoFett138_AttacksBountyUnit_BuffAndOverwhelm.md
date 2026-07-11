# SHD_138 Jango Fett (4-cost 3/6 ground) — "While attacking a unit with a Bounty, +3/+0 and gains
# Overwhelm." Jango (3 power) attacks SHD_095 (2/3, a Bounty unit): +3 → 6 power. 6 damage vs 3 HP → 3
# excess spills to P2's base via Overwhelm. Without the buff Jango would deal exactly 3 (no excess); the
# 3 base damage proves BOTH the +3 and the granted Overwhelm.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_138:1:0
WithP2GroundArena: SHD_095:1:0
WithP1Deck: [SOR_095 SOR_128]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
