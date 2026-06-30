# ASH_185 Intimidation (Event, cost 2) — the draw is gated on controlling a 4+ power unit. P1 controls
# only SOR_095 (3 power), so Intimidation draws nothing (the hand is empty after the event resolves).
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_185}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
