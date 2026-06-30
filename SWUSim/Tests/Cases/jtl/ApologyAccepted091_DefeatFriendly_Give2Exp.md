# JTL_091 Apology Accepted (event) — Defeat a friendly unit. You may give 2 Experience tokens to a unit.
# P1 defeats SOR_095 and gives 2 Experience (+2/+2) to the surviving SEC_080 (3/3 → 5/5).

## GIVEN
CommonSetup: bgk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_091
WithP1Resources: 1
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
