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

---

# TeamSuns_TheTEAMMATESLeaderUnitAlsoDiscounts
#// ⚠ USER RULING 2026-08-26: "for each FRIENDLY leader unit" spans the TEAM, so your teammate's deployed
#// leader cheapens YOUR play.
#//
#// ⚠ This card was MISSED by that day's friendly audit. The sweep matched an `// EpicAction:` header and
#// this clause lives on `// Epic Action:` — with a space. Exactly two cards hid behind that typo, this
#// one and Sundari Palace; both are "for each friendly leader unit".
#//
#// Seat 1 has NO leader unit of its own and seat 3 (its RED teammate) has one, so the entire discount
#// comes from across the table: SEC_080 costs 2 here and seat 1 holds exactly 1 resource, so it is
#// playable ONLY if the teammate's leader counts. That makes affordability itself the assertion, which
#// cannot pass by accident.

## GIVEN
CommonSetup: ggk/rrk/{myBase:TS26_10}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 1
WithP1Hand: SEC_080
#// ⚠ Seat 3's leader must be DEPLOYED — a plain unit is not a LEADER unit, and IsLeaderUnit reads the
#// live arena object. `:1:1` = ready + deployed, which splices a real arena leader unit for the far seat.
WithP3Leader: SHD_014:1:1

## WHEN
- P1>UseBaseAbility

## EXPECT
SEATCOUNT:4
P1HANDCOUNT:0
P1RESAVAILABLE:0
