# Coordinate_Inactive_NoDraw
#// TWI_147 Anakin Skywalker — with Coordinate inactive (Anakin is the only friendly unit), attacking
#// does NOT draw: deck stays 2.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_147:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1DECKCOUNT:2

---

# Coordinate_OnAttack_Draws
#// TWI_147 Anakin Skywalker (Unit 6/6, Ground) — "Coordinate - On Attack: Draw a card." With 3 friendly
#// units (Coordinate active), Anakin attacks P2's base and draws 1 (deck 2 → 1). Anakin power 6 → P2 base 6.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_147:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6
P1DECKCOUNT:1
