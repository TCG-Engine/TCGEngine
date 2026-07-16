# Coordinate_Active_CreatesClone
#// TWI_095 Pelta Supply Frigate (Unit 3/6, Space, cost 5) — "Coordinate - When Played: Create a Clone
#// Trooper token. (including this one)." Played with 2 friendly units already in play → the frigate makes
#// 3 (including itself) → Coordinate active → create 1 Clone Trooper.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:TWI_095}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_095
P1GROUNDARENACOUNT:3

---

# Coordinate_Inactive_NoClone
#// TWI_095 Pelta Supply Frigate — played with only 1 friendly unit already in play → 2 total including
#// itself → Coordinate INACTIVE → no Clone Trooper created.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5;handCardIds:TWI_095}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
