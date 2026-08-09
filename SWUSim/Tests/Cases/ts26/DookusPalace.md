# EpicPlayUnitDiscounted
#// TS26_10 Dooku's Palace (Base, Command) — Epic Action: play a unit from your hand; it costs 1 less per
#// friendly leader unit. With one deployed leader unit, SEC_080 (effective cost 2 here) plays for 1 — only
#// affordable because of the -1 discount (1 resource → 0 left), landing beside the deployed leader.
## GIVEN
CommonSetup: ggk/rrk/{myBase:TS26_10;myLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SEC_080
## WHEN
- P1>UseBaseAbility
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1RESAVAILABLE:0
P1BASE:EPICUSED

---

# NoFriendlyLeaderUnitMeansNoDiscount
#// TS26_10 Dooku's Palace — "It costs 1 resource less FOR EACH friendly LEADER UNIT". With the leader
#// undeployed the count is zero, so SEC_080 still costs its printed 2 and cannot be paid for out of a
#// single resource: it stays in hand and the resource is untouched.
#// Discriminating against EpicPlayUnitDiscounted, which plays the same unit off the same 1 resource
#// precisely because a deployed leader unit is on the board.

## GIVEN
CommonSetup: ggk/rrk/{myBase:TS26_10}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1
WithP1Hand: SEC_080

## WHEN
- P1>UseBaseAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1
