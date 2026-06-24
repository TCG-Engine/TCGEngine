# ASH_014 The Mandalorian — declining the optional payment skips the draw. P1 claims initiative and
# declines, keeping its resource and drawing nothing.
## GIVEN
P1LeaderBase: ASH_014/SOR_024
P2LeaderBase: SOR_010/SOR_020
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
