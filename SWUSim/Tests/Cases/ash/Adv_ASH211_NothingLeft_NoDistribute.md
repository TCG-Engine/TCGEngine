# ASH_211 Fateful Goodbye — the distribute is gated on something having left play this phase. With no unit
# (or leader) having left play, Fateful Goodbye does nothing (SEC_135 gains no Advantage tokens).
## GIVEN
CommonSetup: yyw/yyk/{myResources:2;handCardIds:ASH_211}
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
