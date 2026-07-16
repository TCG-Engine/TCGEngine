# PlaysUnitDiscountedThenDeals4
#// TS26_032 Reckless Landing (Event, cost 2, Aggression/Cunning) — Play a unit from your hand. It costs 4
#// resources less. Deal 4 damage to it.
#// P1 plays the event, then the only playable hand unit (JTL_069 Munificent Frigate, 4/7 space, cost 5;
#// −4 = 3 after the Vigilance off-aspect penalty is budgeted) auto-resolves into play and takes 4 damage.
## GIVEN
CommonSetup: ryk/rrk/{myResources:7}
WithP1Hand: [TS26_032 JTL_069]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:DAMAGE:4
