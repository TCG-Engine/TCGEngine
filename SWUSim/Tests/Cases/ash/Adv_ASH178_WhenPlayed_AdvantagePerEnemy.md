# ASH_178 Knobby White Ice Spider (Ground Creature, 5/7, cost 7, Hidden) — When Played: for each enemy
# unit, give an Advantage token to this unit. P2 has 2 enemy units (one ground, one space) → ASH_178
# enters and gives itself 2 Advantage tokens. (Counts enemy units across BOTH arenas.)
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_178}
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_178
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
