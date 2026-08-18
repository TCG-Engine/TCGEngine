# RegroupCredit
#// LAW_071 The Max Rebo Band (1/5) — When the regroup phase starts: create a Credit token. Pass to
#// regroup -> 1 Credit.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_071:1:0

## WHEN
- P1>Pass

## EXPECT
P1CREDITCOUNT:1

---

# TwoBands_TwoCredits
#// LAW_071 The Max Rebo Band — the regroup trigger belongs to each COPY, so two Bands in play create two
#// Credits at the same regroup. The existing single-Band section cannot tell a per-unit trigger from a
#// once-per-player one.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_071:1:0 LAW_071:1:0]

## WHEN
- P1>Pass

## EXPECT
P1CREDITCOUNT:2

---

# EnemyBand_CreditGoesToITSController
#// LAW_071 The Max Rebo Band — "create a Credit token" creates it for the Band's controller, so a Band on
#// the OTHER side of the table pays the opponent. P2 fields the only Band: at regroup P2 holds 1 Credit
#// and P1 holds none.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP2GroundArena: LAW_071:1:0

## WHEN
- P1>Pass

## EXPECT
P2CREDITCOUNT:1
P1CREDITCOUNT:0

---

# StolenBand_CreditFollowsTheNEWController
#// LAW_071 The Max Rebo Band — controller, not owner. A Band OWNED by P2 but sitting in P1's arena (the
#// end state of a control take) creates its regroup Credit for P1. Together with
#// EnemyBand_CreditGoesToITSController this pins the direction from both sides, which neither an
#// owner-keyed nor a hardcoded-seat implementation could satisfy at once.

## GIVEN
CommonSetup: gyk/bgw/{}
P1OnlyActions: true
WithP1GroundArenaControlled: LAW_071:2

## WHEN
- P1>Pass

## EXPECT
P1CREDITCOUNT:1
P2CREDITCOUNT:0
