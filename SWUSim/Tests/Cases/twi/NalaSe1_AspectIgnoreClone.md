# TWI_001 Nala Se (Leader, front) — "Ignore the aspect penalty on Clone units you play." With Nala Se as
# P1's leader, TWI_109 (Clone, Command, cost 3) plays for its printed 3 despite the off-aspect base, so 3
# resources suffice.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;myLeader:TWI_001;handCardIds:TWI_109}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_109
P1RESAVAILABLE:0
