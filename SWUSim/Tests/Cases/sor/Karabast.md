# FriendlyDamagePlus1
#// SOR_151 Karabast (Event, cost 2) — a friendly unit deals damage to an enemy unit
#// equal to (damage on the friendly unit + 1). P1's Battlefield Marine has 2 damage on
#// it, so it deals 2 + 1 = 3 to P2's Consular Security Force (7 HP → 3 damage, survives).
#// Both selections auto-resolve (one option each).

## GIVEN
CommonSetup: rrw/rrw/{myResources:2;handCardIds:SOR_151}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # friendly dealer with 2 damage
WithP2GroundArena: SOR_046:1:0    # enemy target (3/7)

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
