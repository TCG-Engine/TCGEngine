# SOR_143 Fighters for Freedom — "When you play another [Aggression] card: you may deal 1 to a base."
# FFF#1 in play; P1 plays a SECOND FFF (an Aggression card). FFF#1 reacts → deal 1 to a base.
# Also proves the "another" self-exclusion: only FFF#1 triggers (the just-played FFF#2 is excluded),
# so after one base-deal there is NO second pending decision.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:1
P1NODECISION
