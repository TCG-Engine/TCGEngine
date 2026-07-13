# Twin Suns Phase 3: OpponentsOf returns all LIVE opponents in seat order. Seat 2 eliminated (LiveSeats
# = 1,3), so P1's opponents are just [3] and P3's are just [1]; a dead seat never appears.

## GIVEN
CommonSetup: grw/ggk
WithSeatOrder: 123
WithLiveSeats: 13

## WHEN
- P1>UndoCycle

## EXPECT
SEATCOUNT:3
OPPONENTSOF:1:3
OPPONENTSOF:3:1
OPPONENTSOF:2:1,3
