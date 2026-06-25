# ASH_014 The Mandalorian — declining the optional payment skips the draw. P1 claims initiative and
# declines, keeping its resource and drawing nothing.
## GIVEN
CommonSetup: grw/brk/{
  myLeader:ASH_014
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Deck: SOR_095
## WHEN
- P1>Claim
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:1
