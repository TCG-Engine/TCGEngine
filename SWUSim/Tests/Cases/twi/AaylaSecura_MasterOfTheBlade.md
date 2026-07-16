# Coordinate_Inactive_TakesCounter
#// TWI_096 Aayla Secura — with Coordinate INACTIVE (she's the only friendly unit), the On Attack
#// prevention does not apply: attacking SOR_046 (3/7) she takes the 3 counter damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_096:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# Coordinate_PreventsCounter
#// TWI_096 Aayla Secura (Unit 6/5, Ground) — "Coordinate - On Attack: Prevent all combat damage that
#// would be dealt to this unit for this attack." With 3 friendly units (Coordinate active), Aayla
#// attacks SOR_046 (3/7): she deals 6 (SOR_046 survives at 6 damage) and takes NO counter damage.

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_096:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_096
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:6
