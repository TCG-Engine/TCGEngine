# DefeatedAtRegroup
#// LAW_245 Salvaged Materials — "At the start of the next regroup phase, defeat it." After attaching
#// SOR_071, passing to regroup defeats the upgrade (UPGRADECOUNT back to 0, host power back to 3/3).

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;discardCardIds:SOR_071}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_245

## WHEN
- P1>PlayHand:0
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3

---

# PlayItemFromDiscard
#// LAW_245 Salvaged Materials (Cunning event, cost 1) — "Play an Item upgrade from your discard pile. It
#// costs 3 resources less." SOR_071 Electrostaff (Item, Vigilance) is off-aspect vs the Cunning/Villainy
#// leader: printed 2 + 2 penalty = 4, minus the -3 discount = 1 paid. The attach SUCCEEDS with only 1
#// ready resource left after the event — proving the discount (without it, 4 is unaffordable). Net: 0 ready.

## GIVEN
CommonSetup: yyk/bgw/{myResources:2;discardCardIds:SOR_071}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_245

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:0
