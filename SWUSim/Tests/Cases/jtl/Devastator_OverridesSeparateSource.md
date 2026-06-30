# JTL_143 Devastator's passive override is GLOBAL, not card-specific: while P1 controls Devastator,
# P1's SEPARATE indirect source (JTL_234 Torpedo Barrage → Opponent) is also assigned by P1, not P2.
# Devastator sits in P1's space arena; P1 plays Torpedo Barrage, chooses Opponent, and P1 (not P2)
# assigns the 5 to P2's units/base: 3 to P2's 3/3 (defeats) + 2 to P2 base.
# P1 = ryk (leader Cunning+Villainy) covers JTL_234's Cunning pip → printed cost 3.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP1SpaceArena: JTL_143:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:theirGroundArena-0:3,theirBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
