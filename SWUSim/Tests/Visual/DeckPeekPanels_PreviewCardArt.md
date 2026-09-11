# TopDeckSearch_PreviewCardsRenderAsArt
# VISUAL CHECK — preview (mock) cards render as ART in the three deck-peek panels (2026-09-11)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load each section in the
# Test Schema Editor; every section leaves its panel PENDING, because the prompt IS the thing to look at.
#
# WHY THIS EXISTS — game 103 ("mocks are not showing up in the card chooser"): the opponent's ASH_224
# Elzar Mann search of the top 10 showed nine cards and one broken-image tile — HMW_240 Sandstorm, a
# preview card and the only legal pick. Preview cards keep a plain CardID but their art is stored as
# mock_<CardID>.webp (the corpus has mock_HMW_240.webp and no HMW_240.webp). ShowScryPanel,
# ShowRevealArrangePanel and ShowTopDeckSearchPanel (Core/UILibraries*.js) built the src as
# imgBase + cardID + '.webp' instead of routing it through resolveCardImageID(), so ANY preview card
# peeked off a deck was a broken tile in all three. The static guard is
# SWUSim/DevTools/tests/deck_peek_panel_mock_art_test.php; this file is the pixels.
#
# WHAT TO LOOK AT (this section — "SEARCH THE TOP CARDS")
#   • Five tiles, ALL of them art — no broken-image icon anywhere. HMW_240 Sandstorm is first.
#   • HMW_125 The Marauder and HMW_203 Victor Squadron are SELECTABLE (units — Recruit takes a unit);
#     HMW_240 (an event) and the released SEC_080 are dimmed but still drawn as art.
#   • The released SOR_095 / SEC_080 tiles look exactly as before (the resolver leaves them unchanged).
#   • Check in Chromium AND Firefox (repo cross-browser rule); WebKit will not launch on this machine,
#     so say so rather than implying Safari was covered.
#
# BOARD SHAPE — Recruit (SOR_123, Command, 1) under a Command base; the top 5 mix preview and released
# cards and selectable and dimmed ones, so a regression in EITHER direction (mock art broken, or released
# art mangled by the resolver) is visible in one screenshot.

## GIVEN
CommonSetup: ggw/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_123
WithP1Deck: [HMW_240 HMW_125 SOR_095 HMW_203 SEC_080 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Search_top_cards

---

# Scry_PreviewCardsRenderAsArt
#// SCRY panel (the same bug, second panel). SOR_031 Inferno Four dies attacking into SOR_066 and its
#// When Defeated scries the top 2 — both preview cards. WHAT TO LOOK AT: two tiles, both art
#// (HMW_240 Sandstorm and HMW_125 The Marauder), no broken-image icon.

## GIVEN
CommonSetup: gbk/grw/{myLeader:SOR_001}
SkipPreGame: true
WithP1SpaceArena: SOR_031:1:0
WithP2SpaceArena: SOR_066:1:0
WithP1Deck: [HMW_240 HMW_125 SOR_095]

## WHEN
- P1>AttackSpaceArena:0:0

## EXPECT
P1SPACEARENACOUNT:0
P1HASDECISION
P1DECISIONTOOLTIP:Look_at_top_cards

---

# RevealArrange_PreviewCardsRenderAsArt
#// REVEALARRANGE panel (the third panel). SOR_152 For a Cause I Believe In reveals the top 4 — two of
#// them preview cards — then asks you to discard any and reorder the rest. WHAT TO LOOK AT: four tiles,
#// all art (HMW_240, SOR_095, HMW_125, HMW_203), no broken-image icon.

## GIVEN
CommonSetup: rrw/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_152
WithP1Deck: [HMW_240 SOR_095 HMW_125 HMW_203 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Discard_any_revealed_cards_then_reorder_the_rest_on_top

---

# OptionChooseCardImage_PreviewTopCardRendersAsArt
#// The same raw-CardID path in OptionChooseUI's "@<CardID>" card image (Core/OptionChooseUI.js). SOR_246
#// You're My Only Hope looks at the top card — a preview card, HMW_125 The Marauder — and offers
#// Play/Leave with that card drawn beside the buttons. WHAT TO LOOK AT: the Marauder's art, not a broken
#// icon. (SwuCardArtSrc, the "look at the top card at any time" peek, got the same fix; it has no
#// decision to leave pending, so it is covered by the static test only.)
#// Command base so the Marauder is on-aspect: 7 − 5 = 2 against the 3 left after the event — YMOH
#// skips its prompt entirely when the top card is unaffordable, which would leave nothing to look at.

## GIVEN
CommonSetup: gyw/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_246
WithP1Deck: [HMW_125 SOR_095 SEC_080]

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1DECISIONTOOLTIP:Play_the_top_card_(costs_5_less,_or_free_if_your_base_has_5_or_less_HP)
