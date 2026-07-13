# Phase 5 — The eliminator heals 5 from their own base

# Same KO as Phase5_BaseDefeat, but P1's base starts at 10 damage. After eliminating P3, P1 (the
# last to damage that base) heals 5 → base damage 10 → 5.

## GIVEN
CommonSetup: grw/ggk/{myBaseDamage:10}
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_229:1:0
WithP3Base: SOR_019:27

## WHEN
- P1>AttackGroundArena:0:P3B

## EXPECT
SEATLIVE:3:false
P1BASEDMG:5
