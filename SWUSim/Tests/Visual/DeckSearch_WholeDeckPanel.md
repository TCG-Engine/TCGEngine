# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# VISUAL CHECK — a WHOLE-DECK search (SOR_042 Search Your Feelings) in the top-deck search panel
#
# Bug report 2026-10-01, game 1438045: "Search Your Feelings when played gives a terrible UI/UX". The panel was built for
# a handful of top cards; given the whole deck it drew ~45 full-size cards in an unbounded box, so its title and its
# confirm button were off-screen, in raw deck order.
# Server: SWUSim/Custom/GameLogic.php _topDeckSearchBegin appends segment 6, SCOPE ('deck' | 'top').
# Client: Core/UILibraries20260928.js ShowTopDeckSearchPanel — a LARGE search (scope 'deck', or > 12 cards).

## HOW TO RUN
    cd DevTools/ui-harness && node swusim-deck-search-panel-xbrowser.mjs      # 3 engines x desktop/phone
    cd DevTools/ui-harness && node swusim-topdeck-search-xbrowser.mjs         # small search: centring + minimise
    cd DevTools/ui-harness && node swusim-topdeck-search-wording-xbrowser.mjs # small search: per-card wording
Live: play Search Your Feelings (SOR_042) on any board.

## WHAT TO LOOK AT
1. Title **SEARCH YOUR DECK** (a small top-N search still says SEARCH THE TOP CARDS).
2. The box fits the screen: the title, the "Filter by name or trait" field and the **Take** button are always visible;
   only the card grid scrolls. Desktop and phone.
3. Smaller tiles, **sorted by cost, then name** — so copies sit together.
4. Typing in the filter narrows the grid as you type ("chim" -> the Chimaeras) without losing the field's focus.
5. Picking a card keeps the filter and the grid's scroll position; the button reads "Take 1 card".
6. Desktop: hovering a tile shows the full card preview (a leader shows both faces).
7. A small top-N search (e.g. Recruit) looks exactly as before, apart from never overflowing the screen.
