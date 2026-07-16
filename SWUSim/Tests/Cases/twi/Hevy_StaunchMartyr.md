# Coordinate_Raid2
#// TWI_164 Hevy (Unit 4/4, Ground) — "Coordinate - Raid 2." With 3 friendly units (Coordinate active),
#// Hevy attacking gets +2/+0 → deals 4+2 = 6 to P2's base.

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_164:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6

---

# WhenDefeated_DamageEnemyGround
#// TWI_164 Hevy — "When Defeated: Deal 1 damage to each enemy ground unit." Hevy (pre-damaged to 3, so
#// 1 remaining HP; Coordinate inactive so no Raid) attacks SOR_046 (3/7), dealing 4; the 3-power counter
#// kills Hevy → When Defeated deals 1 to each enemy ground unit (SOR_046 → 5, SEC_080 → 1).

## GIVEN
CommonSetup: rrk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_164:1:3
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:5
P2GROUNDARENAUNIT:1:DAMAGE:1
