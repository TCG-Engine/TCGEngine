# IBH_039 I'll Cover For You (reprint of IBH_005) — same effect: deal 1 to an enemy unit and 1 to
#   another. Confirms the duplicate CardID is wired.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_039
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1NODECISION
