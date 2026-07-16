# VsDamaged_Overwhelm
#// SOR_130 First Legion Snowtrooper (2/3) — the Overwhelm half. Attacking a DAMAGED
#// low-HP enemy: P2's Battlefield Marine (SOR_095, 3/3) starts with 2 damage. The
#// Snowtrooper attacks at 2+2 = 4 power → Marine's damage becomes 2+4 = 6 vs printed
#// HP 3 → defeated with 3 excess, which Overwhelm spills to P2's base (3 damage).
#// (Both units die in the exchange; base takes the overflow.)

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

---

# VsDamaged_PowerBuff
#// SOR_130 First Legion Snowtrooper (2/3) — "While attacking a damaged unit, this
#// unit gets +2/+0 and gains Overwhelm." Attacking a DAMAGED high-HP enemy that
#// survives isolates the +2 power: P2's Consular Security Force (SOR_046, 3/7) starts
#// with 1 damage → Snowtrooper deals 2+2 = 4 → its damage becomes 1+4 = 5 (HP 7, lives).
#// (Snowtrooper takes the 3 counter-damage and dies; no Overwhelm since the defender lives.)

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_130:1:0      # First Legion Snowtrooper (2/3)
WithP2GroundArena: SOR_046:1:1      # Consular Security Force (3/7) with 1 damage

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:0
