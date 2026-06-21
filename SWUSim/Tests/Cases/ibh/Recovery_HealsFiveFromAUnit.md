# IBH_013 Recovery (Event, cost 3, Heroism) — Heal 5 damage from a unit. A friendly 3/7 with 6 damage
#   heals exactly 5 → 1 damage left (a heal-all bug would show 0; proves the amount is 5).

## GIVEN
CommonSetup: bbw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_013
WithP1GroundArena: SOR_046:1:6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1NODECISION
