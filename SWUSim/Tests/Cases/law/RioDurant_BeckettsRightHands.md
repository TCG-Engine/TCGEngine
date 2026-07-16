# BounceReplayShielded
#// LAW_093 Rio Durant (2/5) — When Played: you may return a non-leader unit that costs 3 or less to its
#// owner's hand. Then its owner may play it for free; it gains Shielded for this phase. Return P1's own
#// SEC_080 (cost 2), replay it free with Shielded.

## GIVEN
CommonSetup: byk/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_093

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P1GROUNDARENAUNIT:1:HASKEYWORD:Shielded
