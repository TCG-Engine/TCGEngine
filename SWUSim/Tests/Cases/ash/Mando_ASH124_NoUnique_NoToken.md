# ASH_124 — negative: with no unique unit controlled (only a non-unique filler; ASH_124 itself is
# non-unique), no Mandalorian token is created.
## GIVEN
CommonSetup: ggw/rrk/{myResources:6;handCardIds:ASH_124}
WithP1GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
