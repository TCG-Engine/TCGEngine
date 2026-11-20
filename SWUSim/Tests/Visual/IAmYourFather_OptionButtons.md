# VISUAL CHECK — I Am Your Father: the controller's choice is two labelled buttons, the first with a card link
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Harness:
#   DevTools/ui-harness/swusim-iayf-option-buttons-xbrowser.mjs  (Chromium + Firefox + WebKit, desktop + ?swuLayout=mobile)
#
# WHAT CHANGED (owner, 2026-11-09)
#   SOR_233 I Am Your Father (and its IC27_185 reprint) asked the target's controller a YESNO,
#   "Say no to the 7 damage? (opponent draws 3)". It is now an OPTIONCHOOSE:
#     prompt   "Choose an option"
#     buttons  "<Industrious Team> Takes 7 Damage"   — the unit's name is a CARD LINK (hover = the card preview,
#                                                      the same span.swu-card-link the game log uses)
#              "Opponent Draws 3 Cards"
#   Server side the first option travels as the BARE token "[[LAW_124]]_Takes_7_Damage": Core/OptionChooseUI.js
#   resolves the title client-side, so a title with a space or "&" never enters the delimited Param.
#
# WHAT TO LOOK AT (P2's seat — the CONTROLLER answers, not the caster)
#   1. The banner reads "Choose an option" — not the old yes/no wording.
#   2. Exactly two buttons, in this order: "Industrious Team Takes 7 Damage", "Opponent Draws 3 Cards".
#   3. "Industrious Team" is a card link inside the button (no literal "[[LAW_124]]", no "LAW 124").
#      Hovering it shows the card preview; leaving hides it.
#   4. Clicking "… Takes 7 Damage" closes the banner and deals the 7: the 4/7 Industrious Team is defeated. The game log
#      reads "P2 chose Industrious Team Takes 7 Damage" with the name as a card link (GameLogEvents.php writes the token as
#      [[LAW_124|Industrious Team]]; a blanket "_" → " " would have logged a broken "[[LAW 124]]"). The SWU-PGN CHOICE note
#      offers the plain title.
#   5. The other OPTIONCHOOSE shapes are unchanged: "Ground&Space" text buttons, "@CardID&Play&…" art + text,
#      "P2&P3" seat names.
#   Cross-browser per CLAUDE.md: Chromium, Firefox AND Safari (WebKit), desktop and ?swuLayout=mobile.
#
# BOARD SHAPE
#   One P2 ground unit (LAW_124 Industrious Team, 4/7), so the caster's target pick auto-resolves and the decision
#   pending at P2 IS the prompt under test; 7 damage exactly defeats it, so the click's outcome is visible.
#
# NOT COVERED HERE: which seat answers and both branches' effects — SWUSim/Tests/Cases/sor/IAmYourFather.md
#   (Prompt_OffersBothOutcomesAsButtons pins the raw labels).

## GIVEN
CommonSetup: bbk/brw/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: SOR_233
WithP1Resources: 3
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2HASDECISION
P2OPTIONHAS:[[LAW_124]]_Takes_7_Damage
P2OPTIONHAS:Opponent_Draws_3_Cards
