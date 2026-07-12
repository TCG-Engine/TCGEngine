# TWI_151 Resolute (Unit 8/8, Space, cost 10, Aggression/Heroism, Republic/Vehicle/Capital Ship) — "This
# unit costs 1 resource less to play for every 5 damage on your base." With 10 damage on P1's base the
# discount is -2 (cost 10 → 8). P1 has exactly 8 ready resources, so the play only succeeds because of the
# reduction. No enemy units → the When Played AoE fizzles cleanly.

## GIVEN
CommonSetup: rrw/bbw/{myResources:8;myBaseDamage:10;handCardIds:TWI_151}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:TWI_151
P1RESAVAILABLE:0
