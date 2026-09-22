# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the main menu's deck-check line (SharedUI/Sites/SWUSim/MainMenu.php).
#
# VISUAL CHECK — deck check colours (owner report 2026-09-22: "the deck check shows a checkmark but the text is
#   red on success").
#   The menu revamp gave #queue-inline-error a fixed `swu-note--error` class, and swusim-menu.css sets
#   .swu-note colours with !important — so the inline green that showQueueInlineInfo() used to set lost, and the
#   "✓ Leader / Base — N cards" success line rendered red. The colour is now a STATE class the two helpers swap:
#   swu-note--ok (green #a8c8a0) and swu-note--error (red #ff7b72).

## HOW TO RUN
    cd DevTools/ui-harness && node swusim-deck-check-color-xbrowser.mjs
Screenshots: /tmp/deck-check-<engine>-ok.png, /tmp/deck-check-<engine>-error.png.

## WHAT TO LOOK AT
1. Paste a legal deck link and press a play button: the line under the buttons reads "✓ <leader> / <base> — 50
   cards" in soft GREEN (also "Validating deck…" while it checks).
2. Paste an illegal deck (or an unknown link): the error lines are RED.
3. Fix the deck and try again: the line is green again — it does not stay red from the earlier error.
