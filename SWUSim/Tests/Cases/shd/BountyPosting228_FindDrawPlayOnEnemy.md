# SHD_228 Bounty Posting (Event, cost 1, Cunning)
#   "Search your deck for a Bounty upgrade, reveal it, and draw it. (Shuffle your deck.) You may play that
#    upgrade (paying its cost)."
# P1's deck holds SHD_173 Guild Target (a Bounty upgrade). Playing SHD_228 searches the deck, draws it,
# and P1 chooses to play it. With exactly one enemy unit (SEC_080) as a valid host, it auto-attaches to
# the ENEMY unit (Bounty upgrades attach to enemy units) — proving the search+draw+play chain.

## GIVEN
CommonSetup: yyk/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_228
WithP1Deck: [SHD_173 SOR_095 SEC_080 SOR_128]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SHD_173
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_173
P1HANDCOUNT:0
P1DISCARDCOUNT:1
