# FollowingThePath_RevealedPicksOnTop_AreLogged
#// SSOT #2 (gamelog-updates, 2026-09-11) — putting cards into a deck is one funnel now (SWUPutCardsOnDeck /
#// SWUMoveCardToDeck); ~20 sites built `new Deck(...)` and unshifted/pushed by hand, and the logging was
#// split between SWULogDeckPlacement and SWULogToDeck. The per-site log lines are pinned in
#// core/GameLog_CardMovesToDeckAndResources.md; this file pins what the funnel CHANGED.
#// LOF_103 Following the Path: "Search the top 8 cards of your deck for up to 2 Force units, REVEAL them, and
#// put them on top of your deck in any order. (Put the other cards on the bottom of your deck in a random
#// order.)" Its top placement was a raw unshift with NO log line — only the bottom half was logged. The picks
#// were revealed, so they are named; the rest stays a count. (Fixture from lof/FollowingThePath.md.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:LOF_103}
P1OnlyActions: true
WithP1Deck: LOF_077
WithP1Deck: LOF_050

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_050

## EXPECT
P1DECKTOPCARD:LOF_050
LOGCONTAINS:P1 put [[LOF_050|
LOGCONTAINS:on the top of their deck ([[LOF_103|Following the Path]])
LOGCONTAINS:P1 put 1 card on the bottom of their deck ([[LOF_103|Following the Path]])
