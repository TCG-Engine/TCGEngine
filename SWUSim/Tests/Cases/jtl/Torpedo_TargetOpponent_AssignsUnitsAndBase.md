# JTL_234 Torpedo Barrage (Event, cost 3, Cunning) — "Deal 5 indirect damage to a player."
# P1 plays it and chooses Opponent; P2 (the damaged player) assigns the 5 unpreventable damage
# among their own units + base: 3 to their 3/3 SEC_080 (defeats it) and 2 to their base.
# P1 leader yk = Cunning+Villainy → covers the Cunning pip, JTL_234 plays at printed cost 3.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:3,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1HANDCOUNT:0
