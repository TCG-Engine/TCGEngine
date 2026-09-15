# VISUAL CHECK — undone log lines are struck through; a search's "put N on the bottom" line is no longer gold
#
# Visual-only schema. Lives under Tests/Visual/, which the regression endpoint does NOT scan, so nothing
# here is asserted automatically by the suite. Load it in the Test Schema Editor and Run all, or run the
# automated 3-engine pass: `node DevTools/ui-harness/swusim-log-styles-xbrowser.mjs` (Chromium + Firefox
# + WebKit, desktop AND mobile layout).
#
# WHAT CHANGED (2026-09-11):
#   1. An undo keeps the lines it erased, re-added as "(undone) …" with the log type UNDONE
#      (gamelog-updates #3). Until now UNDONE had NO style, so an undone line read exactly like a live one.
#      It is now dimmed (opacity 0.45) and struck through — on the desktop AND the mobile layout (mobile
#      does not load GameLayout's CSS, so it carries its own copy of the rule).
#   2. SSOT #2 moved a search's "P1 put N cards on the bottom of their deck" line from the REVEAL type
#      (gold) to DECK (the default off-white) — those cards are face DOWN, not revealed. The pick's own
#      "P1 revealed and drew X" line stays REVEAL (gold).
#
# What to look at (the game log, top to bottom after Run all):
#   a. "P1 revealed and drew [[Battlefield Marine]] (Recruit)" — GOLD (REVEAL).
#   b. "P1 put 4 cards on the bottom of their deck (Recruit)" — DEFAULT off-white, NOT gold (DECK).
#   c. "P1 played [[Battlefield Marine]]" — the first play, a live line: full brightness, no strike.
#   d. "(undone) P1 played [[Battlefield Marine]]" — the second play, which the undo erased: DIMMED and
#      STRUCK THROUGH, including the card name (the link fades and is struck with the line).
#   e. "P1 undid their last action" — the last line, a live line again: full brightness, no strike.
#   Negative: only line (d) is struck or dimmed. A struck line (c) or (e) is the bug.
#   Mobile (?swuLayout=mobile): the same five lines, the same styles.
#
# BOARD SHAPE: Recruit first so the log has both a REVEAL line and a DECK line next to each other; two
# copies of SOR_095 so one play stays live and one is undone; 12 resources so every play is affordable.

## GIVEN
CommonSetup: grw/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Hand: SOR_095
WithP1Hand: SOR_095
WithP1Deck: [SOR_251 SOR_095 SOR_172 SOR_073 SOR_124 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_095
- P1>PlayHand:0
- P1>PlayHand:0
- P1>Undo

## EXPECT
P1GROUNDARENACOUNT:1
LOGCOUNT:1:(undone) P1 played [[SOR_095
LASTLOGCONTAINS:P1 undid their last action
