# DamageEqualsUnitsInArena
#// TWI_099 Synchronized Strike (Event, cost 2, Command/Heroism, Tactic) — "Deal damage to an enemy unit
#// equal to the number of units you control in its arena." P1 controls 2 ground units (SOR_095 x2) and 1
#// space unit (SOR_237). The lone enemy unit is a GROUND unit (SOR_046 3/7), so the count is the ground
#// units only = 2 (the space unit does NOT count — proves "in its arena", not total units). Auto-targets
#// the single enemy unit → 2 damage. Base g = Command, leader gw = Heroism → both pips covered.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_099}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoEnemyUnits_Fizzle
#// TWI_099 Synchronized Strike — with no enemy units on the board the event fizzles cleanly: it goes to
#// discard, no decision is pending, and nothing crashes.

## GIVEN
CommonSetup: ggw/rrk/{myResources:2;handCardIds:TWI_099}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:TWI_099
