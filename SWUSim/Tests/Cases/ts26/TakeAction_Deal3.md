# TS26_071 Take Action (Event, cost 3, Aggression) — Deal 3 damage to a unit. (No friendly leader units,
# so no cost reduction here.) The single enemy unit is the only target → auto-resolves.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
WithP1Hand: TS26_071
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
