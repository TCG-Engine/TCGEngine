# HighPower_DrawTwo
#// ASH_185 Intimidation (Event, cost 2) — If you control a unit with 4 or more power, draw 2 cards. P1
#// controls SEC_135 (4 power), so playing Intimidation draws 2 (the hand ends at 2 after the event leaves).
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_185}
WithP1GroundArena: SEC_135:1:0
WithP1Deck: [SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:2

---

# NoHighPower_NoDraw
#// ASH_185 Intimidation (Event, cost 2) — the draw is gated on controlling a 4+ power unit. P1 controls
#// only SOR_095 (3 power), so Intimidation draws nothing (the hand is empty after the event resolves).
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:ASH_185}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_095 SOR_046]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0
