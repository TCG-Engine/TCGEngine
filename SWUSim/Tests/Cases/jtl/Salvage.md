# NoVehicle_Fizzle
#// JTL_121 Salvage — with no Vehicle unit in the discard pile, the event fizzles cleanly (nothing
#// enters play). The discard holds only a non-Vehicle unit (SOR_095) plus the event itself.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_121;discardCardIds:SOR_095}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1NODECISION
P1DISCARDCOUNT:2

---

# PlayVehicleFromDiscard_Deal1
#// JTL_121 Salvage — "Play a Vehicle unit from your discard pile (paying its cost). Then, deal 1 damage
#// to it." P1 plays SOR_237 (Alliance X-Wing, 2/3, cost 1) out of its own discard, and it takes 1 damage.
#// The event JTL_121 stays in the discard afterward (SOR_237 left to enter play).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_121;discardCardIds:SOR_237}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:1
P1DISCARDCOUNT:1
