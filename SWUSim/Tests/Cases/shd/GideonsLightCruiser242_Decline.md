# SHD_242 Gideon's Light Cruiser — control Moff Gideon but DECLINE the optional free-play.
# The offer is a "may" (MZMAYCHOOSE); answering '-' declines, so SEC_080 stays in hand and nothing extra plays.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1NODECISION
