# SHD_228 Bounty Posting — Bounty upgrades attach to ANY unit (friendly OR enemy; the rules allow either).
# With a friendly (SOR_095) and an enemy (SEC_080) both present, playing the drawn Guild Target offers a
# host CHOICE; here P1 picks the FRIENDLY unit, proving friendly is a valid host under the "any unit" ruling.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SEC_080 SOR_128 SOR_046]
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_173
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
