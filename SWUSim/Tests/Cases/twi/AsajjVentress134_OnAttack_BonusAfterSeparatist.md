# TWI_134 Asajj Ventress (Unit 2/4, Ground) — "Exploit 2. On Attack: If you've attacked with another
# Separatist unit this phase, this unit gets +3/+0 for this phase." A Battle Droid (Separatist) attacks
# P2's base first (1 damage); then Asajj attacks — having attacked with another Separatist this phase,
# she gets +3/+0 → deals 2+3 = 5. Total P2 base damage = 6.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_134:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
