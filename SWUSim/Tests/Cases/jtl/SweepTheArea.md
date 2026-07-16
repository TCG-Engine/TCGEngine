# ReturnTwoSameArenaCombined3
#// JTL_233 Sweep the Area (event) — Return up to 2 non-leader units in the same arena with combined cost
#// 3 or less to their owners' hands. P2's SOR_095 (cost 2) and LAW_180 (cost 1) are both ground and total
#// 3, so both return to P2's hand.

## GIVEN
CommonSetup: gyw/bbk/{
  myLeader:JTL_016;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_233
WithP1Resources: 3
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_180:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:2
