# JTL_234 Torpedo Barrage — you may choose ANY player, including yourself (CR 35.1).
# P1 chooses "You": P1 assigns the 5 among their own units + base — 3 to own 3/3 SEC_080
# (Villainy, Side-matches the yk leader; defeats it) and 2 to own base.

## GIVEN
CommonSetup: ryk/rrk/{myResources:3;handCardIds:JTL_234}
WithActivePlayer: 1
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myGroundArena-0:3,myBase-0:2

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2
