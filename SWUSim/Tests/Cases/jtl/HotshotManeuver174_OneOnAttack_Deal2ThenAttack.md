# JTL_174 Hotshot Maneuver — the chosen unit JTL_243 (Quasar TIE Carrier, 5 power) has ONE On Attack
# ability ("create a TIE"), so P1 deals 2 to one enemy unit (SOR_225, 2/1 → dies), THEN attacks with
# JTL_243: its On Attack creates a TIE token and, with no enemy units left, it hits the P2 base for 5.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5
P1SPACEARENACOUNT:2
