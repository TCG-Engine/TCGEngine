# BounceAndOwnerReplays
#// SHD_207 A New Adventure (2-cost event, Cunning) — "Return a non-leader unit that costs 6 or less to its
#// owner's hand. Then, its owner may play it for free." P1 returns the enemy SEC_080 (cost 3); its owner P2
#// chooses to replay it for free, so it returns to P2's ground.

## GIVEN
CommonSetup: yyk/yyk/{myResources:2}
WithActivePlayer: 1
WithP1Hand: SHD_207
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
