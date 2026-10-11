# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the supported-deck-links info tooltip (owner, 2026-09-21)
#
#   The always-visible "Supported deck links: SWUStats, SWUDB, …" line under the deck-link input became an ⓘ info
#   tooltip on the "Paste a deck link:" label. Markup: .swu-label-row / button.swu-info-tip / .swu-info-tip__bubble in
#   MainMenu.php; styles at the end of SharedUI/Sites/SWUSim/css/swusim-overrides.css; tap/Escape JS next to
#   switchDeckTab().

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1400px and 390px):
    cd DevTools/ui-harness && node swusim-deck-link-tooltip-xbrowser.mjs
Screenshots of the OPEN bubble: /tmp/deck-link-tip-<engine>-<width>.png. Then look, by eye, at:
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php   (Deck Link tab)

## WHAT TO LOOK AT

1. **At rest.** "Paste a deck link:" is followed on the same line by a small circled italic "i" in the muted text colour.
   There is NO sites line under the input any more.
2. **The icon is a circle, not a button chip.** A 1px round border, no padding, a lowercase "i". ⚠ The shared menu
   button alias (components.css `button:not(.btn):not(.switch)`) once won the cascade and drew it as a tiny borderless,
   uppercase blob — that is the failure to look for.
3. **Hover (desktop).** The bubble fades in under the label row, spanning the input's width: bold "Supported deck
   links:" then the nine sites. The icon brightens to the accent colour. Moving away hides it.
4. **Keyboard.** Shift+Tab from the deck-link input focuses the icon and opens the bubble; Tab away closes it.
   Safari: Tab skips buttons by default — use Option+Tab (or turn on "Press Tab to highlight each item").
5. **Tap (phone / Safari click).** A tap opens it and it stays open; tapping anywhere else, or Escape, closes it.
   WebKit does not focus a clicked button, so this path is JS (.is-open), not :focus.
6. **Phone width (390px).** The open bubble wraps to three lines and stays inside the "Create a New Game" card.
7. **It floats.** Opening the bubble does not push the input or anything below it down.
