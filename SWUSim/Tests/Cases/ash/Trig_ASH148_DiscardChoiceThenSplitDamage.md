# PROBE C: after P1 plays ASH_148 (P2 holds 2 cards), is P2's discard decision present and whose turn?
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148;
  theirHandCardIds:SEC_142,SEC_144
}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
## EXPECT
P2HASDECISION
P1NODECISION
TURNPLAYER:2
