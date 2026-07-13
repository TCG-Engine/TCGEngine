# Phase 5 — Self-elimination (no damager) heals nobody

# Eliminating a seat with no killer (state-based / self-defeat) must NOT heal anyone. P1's base
# starts at 10 damage and stays there.

## GIVEN
CommonSetup: grw/ggk/{myBaseDamage:10}
WithSeatOrder: 123
WithLiveSeats: 123
WithP3Base: SOR_019
WithActivePlayer: 1

## WHEN
- P1>EliminateSeat:3

## EXPECT
SEATLIVE:3:false
P1BASEDMG:10
