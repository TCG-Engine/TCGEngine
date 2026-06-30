# SEC_061 Willrow Hood — the lone friendly upgrade also can't be RETURNED to hand by an enemy ability.
#   P2's Willrow bears 1 friendly upgrade (SOR_120, cost 2 ≤3). P1 plays SEC_200 Junior Senator ("may
#   return an upgrade that costs 3 or less to its owner's hand") and picks Willrow as the host — but the
#   enemy return is blocked, so SOR_120 stays attached.

## GIVEN
CommonSetup: yyw/grw/{myResources:2;handCardIds:SEC_200}
WithP2GroundArena: SEC_061:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
