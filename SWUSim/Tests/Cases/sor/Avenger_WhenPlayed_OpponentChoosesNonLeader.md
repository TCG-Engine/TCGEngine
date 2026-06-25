# SOR_040 Avenger (8/8 Space, cost 9) — the When Played window with a real choice. P1 plays Avenger;
# the opponent controls TWO non-leader units (SEC_080, SOR_128) and chooses which to defeat. Here the
# opponent picks myGroundArena-1 (SOR_128), leaving SEC_080 (reindexed to 0). SOR_002/SOR_021 cover
# Vigilance+Villainy so Avenger plays at its printed cost 9.

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_040
WithP1Resources: 9
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P1SPACEARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
