# TopDeckSearch_AckbarSaysSpaceUnits
# VISUAL CHECK — the shared search panel uses the CARD's wording, not the first caller's (2026-09-18)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load each section in the
# Test Schema Editor; every section leaves its panel PENDING, because the prompt IS the thing to look at.
#
# WHY THIS EXISTS — the TOPDECKSEARCH panel is shared by ~65 callers, and what makes a card legal is a PHP
# closure that cannot cross the request boundary. So the client had nothing to describe the selection with,
# and the cost-budget subtitle was written against the only caller it had at the time: SOR_087 Darth Vader,
# "any number of Villainy units with combined cost 3 or less". Six more cost callers arrived and inherited
# that sentence. ASH_110 Admiral Ackbar searches for SPACE units and said "Select Villainy units"; the
# confirm button said "Take N cards" over callers that PLAY or DISCARD their picks.
#
# The wording is now a REQUIRED argument of _topDeckSearchBegin, on the wire as param segments 4 (label)
# and 5 (verb). The text assertions live in DevTools/ui-harness/swusim-topdeck-search-wording-xbrowser.mjs
# (39 checks × chromium/firefox/webkit, all green 2026-09-18) and
# SWUSim/DevTools/tests/topdeck_search_panel_wording_test.php. This file is the pixels: that the sentence
# still FITS and reads as a sentence at real panel width with a long label and a full card row.
#
# WHAT TO LOOK AT (this section)
#   • Subtitle reads exactly: Select space units (combined cost ≤ 5). Used: 0/5
#   • The word "Villainy" appears NOWHERE.
#   • The confirm button reads "Play None" — not "Take None"; Ackbar plays its picks for free.
#   • Pick a unit and the button becomes "Play 1 card" and Used: climbs.
#
# BOARD SHAPE — Ackbar (ASH_110, cost 5) under a Command/Heroism base, deck topped with two cheap space
# units so the budget line has something to count.

## GIVEN
CommonSetup: ggw/ggk/{myResources:5;handCardIds:ASH_110}
WithP1Deck: [SOR_225 SOR_237 SOR_046]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1HASDECISION
P1SEARCHLABEL:space units
P1SEARCHVERB:Play

---

# TopDeckSearch_LongLabelStillFits
#// The longest label in the family — ASH_090 Reforge, "upgrades that can attach to that unit" — against a
#// full 8-card peek. WHAT TO LOOK AT: the subtitle stays on one line at desktop width and WRAPS rather
#// than clipping or pushing the panel wider than the viewport at phone width (narrow the window). The
#// panel is centred and the card row wraps beneath it.
#// Reforge first defeats a friendly upgrade, so the board needs one to defeat.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6;handCardIds:ASH_090}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_225:1:0
WithP1Deck: [SOR_046 SOR_237 SOR_225 SOR_095 SEC_080 SOR_152 SOR_123 SOR_104]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION

---

# TopDeckSearch_DiscardVerbOnTheButton
#// LOF_117 Sifo-Dyas DISCARDS its picks (they become free-playable from the discard pile this phase), so a
#// fixed "Take N cards" button was actively misleading about what the confirm does.
#// WHAT TO LOOK AT: subtitle "Select Clone units (combined cost ≤ 4). Used: 0/4"; the button reads
#// "Discard None", and "Discard 1 card" once a Clone is selected.

## GIVEN
CommonSetup: ggw/ggk/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_117:1:0
WithP2GroundArena: SOR_237:1:0
WithP1Deck: [SHD_027 SOR_225 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1SEARCHVERB:Discard
