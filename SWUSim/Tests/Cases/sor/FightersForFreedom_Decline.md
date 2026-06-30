# SOR_143 Fighters for Freedom — decline the optional "deal 1 to a base" reaction.
# Playing another Aggression card triggers FFF, but the player passes → no base damage.

## GIVEN
CommonSetup: rrw/rrk/{myResources:4;handCardIds:SOR_143}
WithP1GroundArena: SOR_143:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1NODECISION
