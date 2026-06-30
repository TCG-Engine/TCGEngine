# IBH_066 Too Strong for Blasters (Event, cost 1, Vigilance) — Heal 2 damage from a unit. A friendly
#   3/7 with 3 damage heals 2 → 1 left (proves the amount is 2, not heal-all).

## GIVEN
CommonSetup: bbw/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: IBH_066
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
