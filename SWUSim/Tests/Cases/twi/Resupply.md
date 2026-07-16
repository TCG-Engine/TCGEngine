# EventToResource
#// TWI_127 Resupply (Event, Command) — "Put this event into play as a resource." After paying its cost the
#// event enters the resource zone (exhausted), so P1 ends with 4 resources.
## GIVEN
CommonSetup: ggw/rrk/{myResources:3;handCardIds:TWI_127}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:4
